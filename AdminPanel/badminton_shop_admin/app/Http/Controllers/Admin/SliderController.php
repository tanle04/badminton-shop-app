<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Slider; // Model Slider
use Illuminate\Support\Facades\Auth; // Để lấy employeeID của người tạo
use Illuminate\Support\Facades\Storage; // Để xử lý file ảnh
use Illuminate\Validation\Rule;

class SliderController extends Controller
{
    /**
     * Hiển thị danh sách Sliders.
     */
    public function index()
    {
        // Eager load employee để hiển thị người tạo
        $sliders = Slider::with('employee')->latest('sliderID')->paginate(10);
        return view('admin.sliders.index', compact('sliders'));
    }

    /**
     * Hiển thị form tạo mới.
     */
    public function create()
    {
        return view('admin.sliders.create');
    }

    /**
     * Lưu Slider mới vào DB.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'imageUrl' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', 
            'backlink' => 'nullable|url|max:255',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        
        // 1. Lưu file ảnh vào storage/app/public/sliders
        $path = $request->file('imageUrl')->store('sliders', 'public');

        // 2. Tạo bản ghi trong DB
        Slider::create([
            'title' => $request->title,
            'imageUrl' => $path, // Lưu đường dẫn file
            'backlink' => $request->backlink,
            'notes' => $request->notes,
            'status' => $request->status,
            // Gắn employeeID của người đang đăng nhập (Guard 'admin' được dùng)
            'employeeID' => Auth::guard('admin')->id(), 
        ]);

        return redirect()->route('admin.sliders.index')
                         ->with('success', 'Slider mới đã được tạo thành công!');
    }

    /**
     * Hiển thị form chỉnh sửa.
     */
    public function edit(Slider $slider)
    {
        return view('admin.sliders.edit', compact('slider'));
    }

    /**
     * Cập nhật Slider.
     */
    public function update(Request $request, Slider $slider)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'imageUrl' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // Nullable khi update
            'backlink' => 'nullable|url|max:255',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data = $request->only(['title', 'backlink', 'notes', 'status']);
        
        // Xử lý upload ảnh mới
        if ($request->hasFile('imageUrl')) {
            // Xóa ảnh cũ
            Storage::disk('public')->delete($slider->imageUrl);
            // Lưu ảnh mới
            $path = $request->file('imageUrl')->store('sliders', 'public');
            $data['imageUrl'] = $path;
        }

        $slider->update($data);

        return redirect()->route('admin.sliders.index')
                         ->with('success', 'Slider đã được cập nhật thành công!');
    }

    /**
     * Xóa Slider.
     */
    public function destroy(Slider $slider)
    {
        try {
            // Xóa ảnh vật lý trước
            Storage::disk('public')->delete($slider->imageUrl);
            // Xóa bản ghi DB
            $slider->delete();

            return redirect()->route('admin.sliders.index')
                             ->with('success', 'Slider đã được xóa.');
        } catch (\Exception $e) {
            return redirect()->back()
                             ->with('error', 'Lỗi khi xóa Slider: ' . $e->getMessage());
        }
    }
}