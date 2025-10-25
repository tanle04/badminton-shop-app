<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\ReviewMedia; // Đảm bảo import
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate; // Để kiểm tra quyền Admin/Staff
use Illuminate\Support\Str; // Để cắt chuỗi

class ReviewController extends Controller
{
    // LƯU Ý: Routes được bảo vệ bởi Gate::allows('staff') ở web.php

    /**
     * Hiển thị danh sách đánh giá.
     */
    public function index()
    {
        // Eager load Customer và Product để hiển thị
        $reviews = Review::with(['customer', 'product'])
            ->latest('reviewDate')
            ->paginate(15);

        return view('admin.reviews.index', compact('reviews'));
    }

    /**
     * Hiển thị chi tiết đánh giá (và form cập nhật trạng thái).
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
        // Kiểm tra quyền (Mặc dù route đã lọc, nên kiểm tra lại cho an toàn)
        if (!Gate::allows('admin') && !Gate::allows('staff')) {
            return redirect()->back()->with('error', 'Bạn không có quyền duyệt đánh giá.');
        }

        $request->validate([
            'status' => ['required', Rule::in(['published', 'pending', 'hidden'])],
        ]);

        $review->status = $request->status;
        $review->save();

        return redirect()->route('admin.reviews.index')
            ->with('success', 'Trạng thái đánh giá đã được cập nhật.');
    }

    /**
     * Xóa đánh giá (Chỉ Admin và Staff).
     */
    public function destroy(Review $review)
    {
        if (!Gate::allows('admin') && !Gate::allows('staff')) {
            return redirect()->back()->with('error', 'Bạn không có quyền xóa đánh giá.');
        }

        try {
            DB::beginTransaction(); // KHÔNG còn lỗi Class not found

            // ⭐ 1. Xóa Media Vật Lý từ thư mục API Client
            $disk = Storage::disk('api_client_uploads'); // KHÔNG còn lỗi Class not found
            $filesToDelete = [];

            foreach ($review->media as $media) {
                $path = $media->mediaUrl;

                // Chuẩn hóa đường dẫn: Loại bỏ các tiền tố không cần thiết (như /api/uploads/)
                if (Str::startsWith($path, '/api/uploads/')) {
                    $path = Str::after($path, '/api/uploads/');
                } elseif (Str::startsWith($path, 'reviews/')) {
                    $path = $path;
                }

                $filesToDelete[] = $path;
            }

            // Thực hiện xóa vật lý hàng loạt
            if (!empty($filesToDelete)) {
                $disk->delete($filesToDelete);
            }

            // 2. Xóa bản ghi đánh giá (sẽ tự động xóa media record nhờ CASCADE)
            $review->delete();

            DB::commit(); // Hoàn tất Transaction

            return redirect()->route('admin.reviews.index')
                ->with('success', 'Đánh giá và tệp media liên quan đã được xóa.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Ghi lỗi chi tiết hơn
            \Log::error('Error deleting review media: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Lỗi khi xóa đánh giá và tệp: ' . $e->getMessage());
        }
    }
}
