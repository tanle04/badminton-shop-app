<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Slider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class SliderController extends Controller
{
    /**
     * Hiển thị danh sách sliders
     */
    public function index()
    {
        $sliders = Slider::with('employee')
                        ->orderBy('displayOrder', 'asc')
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);
        
        return view('admin.sliders.index', compact('sliders'));
    }

    /**
     * API: Lấy danh sách sliders (cho AJAX)
     */
    public function apiIndex(Request $request)
    {
        $query = Slider::with('employee');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('backlink', 'LIKE', "%{$search}%")
                  ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'displayOrder');
        $sortDir = $request->get('sort_dir', 'asc');
        
        $allowedSorts = ['displayOrder', 'title', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = $request->get('per_page', 10);
        $sliders = $query->paginate($perPage);

        return response()->json($sliders);
    }

    /**
     * API: Cập nhật thứ tự hiển thị (drag & drop)
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:sliders,sliderID',
            'orders.*.order' => 'required|integer|min:0'
        ]);

        try {
            foreach ($request->orders as $item) {
                Slider::where('sliderID', $item['id'])
                      ->update(['displayOrder' => $item['order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật thứ tự hiển thị!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hiển thị form tạo slider mới
     */
    public function create()
    {
        return view('admin.sliders.create');
    }

    /**
     * Lưu slider mới
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'backlink' => 'nullable|url|max:500',
            'imageUrl' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB
            'notes' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive'
        ], [
            'imageUrl.required' => 'Vui lòng chọn hình ảnh slider',
            'imageUrl.image' => 'File phải là hình ảnh',
            'imageUrl.mimes' => 'Chỉ chấp nhận: jpeg, jpg, png, gif, webp',
            'imageUrl.max' => 'Kích thước ảnh tối đa 5MB',
            'backlink.url' => 'Đường dẫn không hợp lệ'
        ]);

        // Upload image
        if ($request->hasFile('imageUrl')) {
            $path = $request->file('imageUrl')->store('sliders', 'public');
            $validated['imageUrl'] = $path;
        }

        // Get max displayOrder and add 1
        $maxOrder = Slider::max('displayOrder') ?? 0;
        $validated['displayOrder'] = $maxOrder + 1;

        // Set employeeID
        $validated['employeeID'] = Auth::id();

        Slider::create($validated);

        return redirect()
            ->route('admin.sliders.index')
            ->with('success', 'Slider đã được tạo thành công!');
    }

    /**
     * Hiển thị form chỉnh sửa
     */
    public function edit(Slider $slider)
    {
        return view('admin.sliders.edit', compact('slider'));
    }

    /**
     * Cập nhật slider
     */
    public function update(Request $request, Slider $slider)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'backlink' => 'nullable|url|max:500',
            'imageUrl' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'notes' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive'
        ], [
            'imageUrl.image' => 'File phải là hình ảnh',
            'imageUrl.mimes' => 'Chỉ chấp nhận: jpeg, jpg, png, gif, webp',
            'imageUrl.max' => 'Kích thước ảnh tối đa 5MB',
            'backlink.url' => 'Đường dẫn không hợp lệ'
        ]);

        // Upload new image if provided
        if ($request->hasFile('imageUrl')) {
            // Delete old image
            if ($slider->imageUrl && Storage::disk('public')->exists($slider->imageUrl)) {
                Storage::disk('public')->delete($slider->imageUrl);
            }

            $path = $request->file('imageUrl')->store('sliders', 'public');
            $validated['imageUrl'] = $path;
        }

        $slider->update($validated);

        return redirect()
            ->route('admin.sliders.index')
            ->with('success', 'Slider đã được cập nhật!');
    }

    /**
     * Xóa slider
     */
    public function destroy(Slider $slider)
    {
        try {
            // Delete image
            if ($slider->imageUrl && Storage::disk('public')->exists($slider->imageUrl)) {
                Storage::disk('public')->delete($slider->imageUrl);
            }

            $slider->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Đã xóa slider!'
                ]);
            }

            return redirect()
                ->route('admin.sliders.index')
                ->with('success', 'Slider đã được xóa!');
                
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi: ' . $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Không thể xóa slider: ' . $e->getMessage());
        }
    }

    /**
     * Toggle status (active/inactive)
     */
    public function toggleStatus(Slider $slider)
    {
        try {
            $slider->status = $slider->status === 'active' ? 'inactive' : 'active';
            $slider->save();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái!',
                'status' => $slider->status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }
}