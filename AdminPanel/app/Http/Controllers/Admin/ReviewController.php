<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\ReviewMedia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class ReviewController extends Controller
{
    /**
     * Hiển thị danh sách đánh giá với bộ lọc
     */
    public function index(Request $request)
    {
        // Get filter parameters
        $statusFilter = $request->get('status', 'all');
        $ratingFilter = $request->get('rating', 'all');
        $search = $request->get('search', '');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Start query with relationships
        $query = Review::with(['customer', 'product']);

        // Apply status filter
        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        // Apply rating filter
        if ($ratingFilter && $ratingFilter !== 'all') {
            $query->where('rating', $ratingFilter);
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reviewContent', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($subQ) use ($search) {
                        $subQ->where('fullName', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($subQ) use ($search) {
                        $subQ->where('productName', 'like', "%{$search}%");
                    });
            });
        }

        // Apply date range filter
        if ($dateFrom) {
            $query->whereDate('reviewDate', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('reviewDate', '<=', $dateTo);
        }

        // Order by latest
        $query->orderBy('reviewDate', 'desc');

        // Paginate
        $reviews = $query->paginate(15);

        // Calculate statistics
        $stats = $this->calculateStats();

        return view('admin.reviews.index', compact('reviews', 'stats'));
    }

    /**
     * Tính toán thống kê đánh giá
     */
    private function calculateStats()
    {
        return [
            'total' => Review::count(),
            'pending' => Review::where('status', 'pending')->count(),
            'published' => Review::where('status', 'published')->count(),
            'hidden' => Review::where('status', 'hidden')->count(),
            'avg_rating' => round(Review::where('status', 'published')->avg('rating'), 2),
            'total_5_star' => Review::where('rating', 5)->count(),
            'total_1_star' => Review::where('rating', 1)->count(),
        ];
    }

    /**
     * Hiển thị chi tiết đánh giá.
     */
    public function show(Review $review)
    {
        $review->load(['customer', 'product', 'media']);

        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Cập nhật trạng thái đánh giá (Chỉ Admin và Staff).
     */
    public function update(Request $request, Review $review)
    {
        if (!Gate::allows('admin') && !Gate::allows('staff')) {
            return redirect()->back()->with('error', 'Bạn không có quyền duyệt đánh giá.');
        }

        $request->validate([
            'status' => ['required', Rule::in(['published', 'pending', 'hidden'])],
        ]);

        $review->status = $request->status;
        $review->save();

        // GIỐNG OrderController: Redirect về 'show' thay vì 'index'
        return redirect()->route('admin.reviews.show', $review)
            ->with('success', 'Trạng thái đánh giá đã được cập nhật.');
    }

    /**
     * Xóa đánh giá (Chỉ Admin và Staff).
     */
    /**
     * Xóa đánh giá (Chỉ Admin và Staff).
     */
    public function destroy(Review $review)
    {
        if (!Gate::allows('admin') && !Gate::allows('staff')) {
            return redirect()->back()->with('error', 'Bạn không có quyền xóa đánh giá.');
        }

        try {
            DB::beginTransaction();

            // 1. Xóa Media Vật Lý từ thư mục API Client
            $disk = Storage::disk('api_client_uploads');
            $filesToDelete = [];

            foreach ($review->media as $media) {
                $path = $media->mediaUrl;
                if (Str::startsWith($path, '/api/uploads/')) {
                    $path = Str::after($path, '/api/uploads/');
                }
                $filesToDelete[] = $path;
            }

            if (!empty($filesToDelete)) {
                $disk->delete($filesToDelete);
            }

            // 2. Xóa bản ghi đánh giá (sẽ tự động xóa media record nhờ CASCADE)
            $review->delete();

            DB::commit();

            // Xóa xong thì về 'index' vì 'show' không còn tồn tại
            return redirect()->route('admin.reviews.index')
                ->with('success', 'Đánh giá và tệp media liên quan đã được xóa.');
        } catch (\Exception $e) {
            DB::rollBack();

            // ⭐ SỬA LỖI Ở ĐÂY: Dùng một dấu chấm (.)
            \Log::error('Error deleting review media: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Lỗi khi xóa đánh giá và tệp: ' . $e->getMessage());
        }
    }

    /**
     * Export (future feature)
     */
    public function export(Request $request)
    {
        return redirect()->back()->with('info', 'Chức năng xuất Excel đang được phát triển');
    }

    /**
     * Bulk update (future feature)
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,reviewID',
            'action' => 'required|in:publish,hide,pending'
        ]);

        $reviewIds = $request->review_ids;
        $action = $request->action;
        $updated = 0;

        $newStatus = 'pending';
        if ($action === 'publish') $newStatus = 'published';
        if ($action === 'hide') $newStatus = 'hidden';

        try {
            $updated = Review::whereIn('reviewID', $reviewIds)->update(['status' => $newStatus]);

            return redirect()->back()->with('success', "Đã cập nhật $updated đánh giá thành công!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    // Resource methods (mimicking OrderController)
    public function create()
    {
        abort(404);
    }
    public function store(Request $request)
    {
        abort(404);
    }
    public function edit(Review $review)
    {
        return $this->show($review);
    }
}
