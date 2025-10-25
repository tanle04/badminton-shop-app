@extends('adminlte::page')

@section('title', 'Duyệt Đánh giá #' . $review->reviewID)

@section('content_header')
<h1>Duyệt Đánh giá Sản phẩm</h1>
@stop

@section('content')
<div class="row">
    {{-- Cột 1: Nội dung Đánh giá --}}
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Thông tin Đánh giá</h3>
            </div>
            <div class="card-body">
                <p><strong>Sản phẩm:</strong> <a href="{{ route('admin.products.edit', $review->productID) }}">{{ $review->product->productName ?? 'N/A' }}</a></p>
                <p><strong>Khách hàng:</strong> {{ $review->customer->fullName ?? 'N/A' }}</p>
                <p><strong>Rating:</strong> <span class="text-warning font-weight-bold">{{ $review->rating }}</span> <i class="fas fa-star text-warning"></i></p>
                <hr>
                <h5>Nội dung:</h5>
                <p>{{ $review->reviewContent ?? '(Không có nội dung chi tiết)' }}</p>
                <hr>
                
                {{-- ⭐ KHỐI CODE ĐÃ SỬA: SỬ DỤNG CSS CHO ẢNH ĐÚNG TỶ LỆ --}}
                <h5>Media (Ảnh/Video):</h5>
                <div class="row">
                    @forelse ($review->media as $media)
                    @php
                        // 1. Chuẩn hóa đường dẫn: Đưa về dạng tương đối từ thư mục 'uploads'
                        $path = $media->mediaUrl;
                        if (\Str::startsWith($path, '/api/uploads/')) {
                            $path_relative = \Str::after($path, '/api/uploads/');
                        } else {
                            $path_relative = $path;
                        }
                        
                        // 2. TẠO URL TRUY CẬP CUỐI CÙNG
                        $base_url_correct = 'http://127.0.0.1/api/uploads/';
                        $full_url = $base_url_correct . $path_relative;
                        
                        // 3. Kiểm tra kiểu tệp
                        $path_lower = strtolower($path_relative);
                        $isImage = \Str::endsWith($path_lower, ['.jpg', '.jpeg', '.png', '.gif']);
                        $isVideo = \Str::endsWith($path_lower, ['.mp4', '.mov', '.webm', '.avi']); 
                        
                        // 4. Xác định Class CSS cho cột
                        $col_class = $isVideo ? 'col-md-6' : 'col-md-3';
                    @endphp
                    
                    <div class="{{ $col_class }} mb-3">
                        @if ($isImage)
                            {{-- ⭐ SỬA CSS ẢNH: Xóa height cố định và dùng max-height/aspect ratio --}}
                            <img src="{{ $full_url }}" alt="Review Media" style="width: 100%; max-height: 200px; object-fit: contain; border: 1px solid #ccc;">
                        @elseif ($isVideo)
                            {{-- Hiển thị VIDEO --}}
                            <video controls style="width: 100%; max-height: 250px; border: 1px solid #ccc;">
                                <source src="{{ $full_url }}" type="video/mp4">
                                Trình duyệt của bạn không hỗ trợ video.
                            </video>
                        @else
                            {{-- Placeholder cho các tệp khác --}}
                            <div style="width: 100%; height: 100px; background-color: #eee; text-align: center; padding-top: 30px;">
                                <i class="fas fa-file fa-2x text-secondary"></i>
                            </div>
                        @endif
                        <p class="text-xs text-center mt-1">{{ $media->mediaType }}</p>
                    </div>
                    @empty
                    <div class="col-12"><p>Không có tệp media nào đính kèm.</p></div>
                    @endforelse
                </div>
                {{-- KẾT THÚC KHỐI CODE ĐÃ SỬA --}}
            </div>
        </div>
    </div>

    {{-- Cột 2: Xử lý Trạng thái --}}
    <div class="col-md-4">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">Xử lý Trạng thái</h3>
            </div>
            <div class="card-body">
                <p><strong>Trạng thái hiện tại:</strong>
                    <span class="badge 
                            @if($review->status == 'published') badge-success 
                            @elseif($review->status == 'pending') badge-warning 
                            @else badge-danger @endif">{{ $review->status }}</span>
                </p>

                @if(Gate::allows('admin') || Gate::allows('staff'))
                <form action="{{ route('admin.reviews.update', $review) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label for="status">Thay đổi Trạng thái</label>
                        <select name="status" class="form-control" required>
                            <option value="pending" {{ $review->status == 'pending' ? 'selected' : '' }}>Chờ duyệt (Pending)</option>
                            <option value="published" {{ $review->status == 'published' ? 'selected' : '' }}>Đã xuất bản (Published)</option>
                            <option value="hidden" {{ $review->status == 'hidden' ? 'selected' : '' }}>Ẩn (Hidden)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-warning btn-block">Cập nhật Trạng thái</button>
                </form>

                <hr>
                <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa đánh giá này?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">Xóa Đánh giá</button>
                </form>
                @else
                <div class="alert alert-info mt-3">
                    Bạn không có quyền xử lý/duyệt đánh giá.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@stop