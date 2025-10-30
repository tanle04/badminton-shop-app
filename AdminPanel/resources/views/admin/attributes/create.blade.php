@extends('adminlte::page')

@section('title', 'Thêm Thuộc tính Sản phẩm')

@section('content_header')
    <h1>Thêm Thuộc tính Sản phẩm</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Nhập thông tin Thuộc tính</h3>
                </div>

                {{-- Form với ID để button có thể submit --}}
                <form id="attributeForm" action="{{ route('admin.attributes.store') }}" method="POST">
                    @csrf

                    <div class="card-body">
                        <div class="form-group">
                            <label for="attributeName">
                                Tên Thuộc tính 
                                <span class="text-danger">*</span>
                            </label>
                            
                            <input type="text" 
                                   class="form-control @error('attributeName') is-invalid @enderror" 
                                   id="attributeName" 
                                   name="attributeName" 
                                   placeholder="Ví dụ: Size Giày, Size Quần Áo, Màu Sắc, Grip"
                                   value="{{ old('attributeName') }}" 
                                   required
                                   maxlength="100"
                                   autofocus>
                            
                            @error('attributeName')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror

                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Lưu ý:</strong> Tạo thuộc tính riêng cho từng loại sản phẩm
                                <br>
                                <span class="text-success">✓ Size Giày</span> (cho giày cầu lông) 
                                <br>
                                <span class="text-success">✓ Size Quần Áo</span> (cho áo, quần) 
                                <br>
                                <span class="text-warning">✗ Size</span> (tránh dùng tên chung gây nhầm lẫn)
                            </small>
                        </div>

                        {{-- Gợi ý các thuộc tính thông dụng --}}
                        <div class="alert alert-info">
                            <strong><i class="fas fa-lightbulb"></i> Gợi ý thuộc tính:</strong>
                            <div class="mt-2">
                                <span class="badge badge-primary mr-1">Size Giày</span>
                                <span class="badge badge-primary mr-1">Size Quần Áo</span>
                                <span class="badge badge-primary mr-1">Màu Sắc</span>
                                <span class="badge badge-primary mr-1">Grip</span>
                                <span class="badge badge-primary mr-1">Trọng Lượng</span>
                                <span class="badge badge-primary mr-1">Chất Liệu</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu Thuộc tính
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Hướng dẫn bên phải --}}
        <div class="col-md-4">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-question-circle"></i> Hướng dẫn
                    </h3>
                </div>
                <div class="card-body">
                    <h5>Quy tắc đặt tên</h5>
                    <ul>
                        <li>Tên rõ ràng, dễ hiểu</li>
                        <li>Tách riêng cho từng loại sản phẩm</li>
                        <li>Không trùng lặp</li>
                    </ul>

                    <h5 class="mt-3">Ví dụ phân loại</h5>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Danh mục</th>
                                <th>Thuộc tính</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Vợt</td>
                                <td>
                                    Grip<br>
                                    Trọng Lượng<br>
                                    Màu Sắc
                                </td>
                            </tr>
                            <tr>
                                <td>Giày</td>
                                <td>
                                    Size Giày<br>
                                    Màu Sắc
                                </td>
                            </tr>
                            <tr>
                                <td>Quần Áo</td>
                                <td>
                                    Size Quần Áo<br>
                                    Màu Sắc
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Focus vào input khi load trang
        $('#attributeName').focus();
        
        // Validate trước khi submit
        $('#attributeForm').on('submit', function(e) {
            const name = $('#attributeName').val().trim();
            
            if (name.length < 2) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Tên thuộc tính phải có ít nhất 2 ký tự!'
                });
                return false;
            }
            
            // Cảnh báo nếu dùng tên "Size" chung chung
            if (name.toLowerCase() === 'size') {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Tên không rõ ràng',
                    html: 'Tên thuộc tính "<strong>Size</strong>" quá chung chung.<br>' +
                          'Bạn nên dùng:<br>' +
                          '• <strong>Size Giày</strong> cho giày<br>' +
                          '• <strong>Size Quần Áo</strong> cho quần áo',
                    confirmButtonText: 'Sửa lại',
                    showCancelButton: true,
                    cancelButtonText: 'Vẫn tạo'
                }).then((result) => {
                    if (result.dismiss === Swal.DismissReason.cancel) {
                        $('#attributeForm').off('submit').submit();
                    }
                });
                return false;
            }
        });
    });
</script>
@endsection