@extends('adminlte::page')

@section('title', 'Chi tiết Đánh giá #' . $review->reviewID)

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-star-half-alt"></i> Chi tiết Đánh giá #{{ $review->reviewID }}
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.reviews.index') }}">Đánh giá</a></li>
                <li class="breadcrumb-item active">#{{ $review->reviewID }}</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="row">
        {{-- Cột 1: Thông tin Đánh giá & Nội dung --}}
        <div class="col-lg-8">
            {{-- Card: Customer & Product Info --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Thông tin Khách hàng & Sản phẩm
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Mã đánh giá:</dt>
                        <dd class="col-sm-8"><strong class="text-primary">#{{ $review->reviewID }}</strong></dd>
                        
                        <dt class="col-sm-4">Ngày đánh giá:</dt>
                        <dd class="col-sm-8">{{ \Carbon\Carbon::parse($review->reviewDate)->format('d/m/Y H:i') }}</dd>
                        
                        <dt class="col-sm-4">Khách hàng:</dt>
                        <dd class="col-sm-8"><strong>{{ $review->customer->fullName ?? 'N/A' }}</strong></dd>
                        
                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8">{{ $review->customer->email ?? 'N/A' }}</dd>
                        
                        <dt class="col-sm-4">Sản phẩm:</dt>
                        <dd class="col-sm-8">
                            <a href="{{ $review->product ? route('admin.products.edit', $review->productID) : '#' }}">
                                {{ $review->product->productName ?? 'Sản phẩm không tồn tại' }}
                            </a>
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Card: Content & Media --}}
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-comment"></i> Nội dung & Media
                    </h3>
                </div>
                <div class="card-body">
                    <h5>Nội dung:</h5>
                    <p>{{ $review->reviewContent ?? '(Không có nội dung chi tiết)' }}</p>
                    <hr>
                    
                    <h5>Media (Ảnh/Video):</h5>
                    <div class="row">
                        @forelse ($review->media as $media)
                        @php
                            $path = $media->mediaUrl;
                            if (\Str::startsWith($path, '/api/uploads/')) {
                                $path_relative = \Str::after($path, '/api/uploads/');
                            } else {
                                $path_relative = $path;
                            }
                            $base_url_correct = 'http://127.0.0.1/api/uploads/'; // Cần cấu hình URL này
                            $full_url = $base_url_correct . $path_relative;
                            
                            $path_lower = strtolower($path_relative);
                            $isImage = \Str::endsWith($path_lower, ['.jpg', '.jpeg', '.png', '.gif']);
                            $isVideo = \Str::endsWith($path_lower, ['.mp4', '.mov', '.webm', '.avi']); 
                            $col_class = $isVideo ? 'col-md-6' : 'col-md-3';
                        @endphp
                        
                        <div class="{{ $col_class }} mb-3">
                            @if ($isImage)
                                <img src="{{ $full_url }}" alt="Review Media" style="width: 100%; max-height: 200px; object-fit: contain; border: 1px solid #ccc;">
                            @elseif ($isVideo)
                                <video controls style="width: 100%; max-height: 250px; border: 1px solid #ccc;">
                                    <source src="{{ $full_url }}" type="video/mp4">
                                    Trình duyệt của bạn không hỗ trợ video.
                                </video>
                            @else
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
                </div>
            </div>
        </div>

        {{-- Cột 2: Trạng thái & Xử lý --}}
        <div class="col-lg-4">
            {{-- Card: Status Overview --}}
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-check-circle"></i> Trạng thái
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Rating:</dt>
                        <dd class="col-sm-6">
                            <strong class="text-warning">{{ $review->rating }} <i class="fas fa-star"></i></strong>
                        </dd>
                        
                        <dt class="col-sm-6">Trạng thái:</dt>
                        <dd class="col-sm-6">
                            @php
                                $statusColors = [
                                    'published' => 'success',
                                    'pending' => 'warning',
                                    'hidden' => 'danger'
                                ];
                                $color = $statusColors[$review->status] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $color }}">
                                {{ $review->status }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>

            {{-- Card: Update Status --}}
            @if(Gate::allows('admin') || Gate::allows('staff'))
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Cập nhật Trạng thái
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.reviews.update', $review) }}" method="POST" id="updateReviewForm">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="status">Thay đổi Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="pending" {{ $review->status == 'pending' ? 'selected' : '' }}>Chờ duyệt (Pending)</option>
                                <option value="published" {{ $review->status == 'published' ? 'selected' : '' }}>Đã xuất bản (Published)</option>
                                <option value="hidden" {{ $review->status == 'hidden' ? 'selected' : '' }}>Ẩn (Hidden)</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning btn-block">
                            <i class="fas fa-save"></i> Cập nhật Trạng thái
                        </button>
                    </form>
                </div>
            </div>

            {{-- Card: Delete Action --}}
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trash"></i> Xóa Đánh giá
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" id="deleteReviewForm">
                        @csrf
                        @method('DELETE')
                        <p class="text-muted">Hành động này sẽ xóa vĩnh viễn đánh giá và các tệp media đính kèm. Không thể hoàn tác.</p>
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="fas fa-trash"></i> Xóa vĩnh viễn
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- Card: Review Timeline --}}
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Lịch sử
                    </h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="time-label">
                            <span class="bg-primary">
                                {{ \Carbon\Carbon::parse($review->reviewDate)->format('d/m/Y') }}
                            </span>
                        </div>
                        
                        <div>
                            <i class="fas fa-star bg-info"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="far fa-clock"></i> 
                                    {{ \Carbon\Carbon::parse($review->reviewDate)->format('H:i') }}
                                </span>
                                <h3 class="timeline-header">Đánh giá được tạo</h3>
                                <div class="timeline-body">
                                    Khách hàng: {{ $review->customer->fullName ?? 'Khách lẻ' }}
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <i class="far fa-clock bg-gray"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .card-outline {
        border-top: 3px solid;
    }
    
    .timeline {
        position: relative;
        margin: 0 0 30px 0;
        padding: 0;
        list-style: none;
    }
    
    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #ddd;
        left: 31px;
        margin: 0;
        border-radius: 2px;
    }
</style>
@stop

@section('js')
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    console.log('✅ Review show page loaded');
    
    // Form confirmation (UPDATE)
    $('#updateReviewForm').on('submit', function(e) {
        e.preventDefault();
        
        const status = $('#status').val();
        
        Swal.fire({
            title: 'Xác nhận cập nhật?',
            html: `Bạn đang thay đổi:<br>
                   <strong>Trạng thái:</strong> ${status}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-save"></i> Cập nhật',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            customClass: {
                confirmButton: 'btn btn-warning mr-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });

    // Form confirmation (DELETE)
    $('#deleteReviewForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Bạn có chắc chắn muốn xóa?',
            text: "Hành động này sẽ xóa vĩnh viễn đánh giá và media. Không thể hoàn tác!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Xóa ngay!',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            customClass: {
                confirmButton: 'btn btn-danger mr-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
    
    // Auto dismiss alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@stop