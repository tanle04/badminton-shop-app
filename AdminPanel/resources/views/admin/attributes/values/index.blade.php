@extends('adminlte::page')

{{-- Lấy tên thuộc tính từ biến $attribute --}}
@section('title', 'Giá trị cho: ' . $attribute->attributeName)

@section('content_header')
    <div class="row">
        <div class="col-sm-8">
            {{-- Tiêu đề rõ ràng, chỉ rõ đang quản lý giá trị cho thuộc tính nào --}}
            <h1>
                Quản lý Giá trị cho: <strong>{{ $attribute->attributeName }}</strong>
            </h1>
        </div>
        <div class="col-sm-4">
            {{-- Nút quay lại danh sách TẤT CẢ thuộc tính --}}
            <a href="{{ route('admin.attributes.index') }}" class="btn btn-default float-right">
                <i class="fas fa-arrow-left"></i> Quay lại Danh sách Thuộc tính
            </a>
        </div>
    </div>
@endsection

@section('content')
    {{-- Hiển thị thông báo (success/error) --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i>
            <strong>Thành công!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Lỗi!</strong> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- 
      SECTION: Thêm Giá trị Mới
      Đặt form thêm mới ngay trên trang này để tiện thao tác
    --}}
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-plus"></i> Thêm Giá trị Mới</h3>
        </div>
        
        {{-- Form này trỏ đến route 'storeValue' --}}
        <form action="{{ route('admin.attributes.values.store', $attribute->attributeID) }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="valueName">Tên Giá trị</label>
                    <input type="text" 
                           class="form-control @error('valueName') is-invalid @enderror" 
                           id="valueName" 
                           name="valueName" 
                           placeholder="Ví dụ: S, M, L, Đỏ, Xanh, 40, 41..." 
                           value="{{ old('valueName') }}"
                           required>
                           
                    {{-- Hiển thị lỗi validation --}}
                    @error('valueName')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Lưu Giá trị
                </button>
            </div>
        </form>
    </div>

    {{-- 
      SECTION: Danh sách các Giá trị Hiện có
    --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list"></i> Danh sách Giá trị</h3>
        </div>
        <div class="card-body">
            <table id="valuesTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width: 10%">ID</th>
                        <th style="width: 40%">Tên Giá trị</th>
                        <th style="width: 20%">Đang sử dụng</th>
                        <th style="width: 30%">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($values as $value)
                        <tr>
                            <td>{{ $value->valueID }}</td>
                            <td><strong>{{ $value->valueName }}</strong></td>
                            <td>
                                {{-- Hiển thị số lượng biến thể đang dùng giá trị này --}}
                                @if($value->usageCount > 0)
                                    <span class="badge badge-success">
                                        <i class="fas fa-box"></i> {{ $value->usageCount }} biến thể
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-minus-circle"></i> Chưa dùng
                                    </span>
                                @endif
                            </td>
                            <td>
                                {{-- Nút Sửa (Mở Modal) --}}
                                <button type="button" 
                                        class="btn btn-sm btn-warning btn-edit-value"
                                        title="Chỉnh sửa"
                                        data-value-id="{{ $value->valueID }}"
                                        data-value-name="{{ $value->valueName }}"
                                        data-update-url="{{ route('admin.attributes.values.update', [$attribute->attributeID, $value->valueID]) }}">
                                    <i class="fas fa-edit"></i> Sửa
                                </button>

                                {{-- Nút Xóa (Dùng JS) --}}
                                <button type="button" 
                                        class="btn btn-sm btn-danger btn-delete-value"
                                        title="Xóa"
                                        data-value-id="{{ $value->valueID }}"
                                        data-value-name="{{ $value->valueName }}"
                                        data-usage-count="{{ $value->usageCount }}">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>

                                {{-- Form Xóa ẩn (để JS gọi) --}}
                                <form id="delete-form-{{ $value->valueID }}" 
                                      action="{{ route('admin.attributes.values.destroy', [$attribute->attributeID, $value->valueID]) }}" 
                                      method="POST" 
                                      style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>Chưa có giá trị nào cho thuộc tính này.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 
      SECTION: Modal Chỉnh Sửa Giá trị
      Modal này sẽ được JS kích hoạt và điền dữ liệu vào
    --}}
    <div class="modal fade" id="editValueModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {{-- Form này sẽ được JS set action --}}
                <form id="editValueForm" method="POST">
                    @csrf
                    @method('PUT') {{-- Dùng method PUT/PATCH cho update --}}
                    
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title"><i class="fas fa-edit"></i> Chỉnh sửa Giá trị</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                           <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="editValueName">Tên Giá trị</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="editValueName" 
                                   name="valueName" 
                                   required>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('css')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
    <style>
        /* Tăng khoảng cách giữa các nút hành động */
        .btn-sm {
            margin-right: 5px;
        }
        /* Fix khoảng cách cho nút SweetAlert2 */
        .swal2-actions {
            gap: 10px;
        }
    </style>
@endsection

@section('js')
    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Khởi tạo DataTable
            $('#valuesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json"
                },
                "paging": true,
                "lengthChange": false, // Ẩn "Show X entries"
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "order": [[0, 'asc']], // Sắp xếp theo ID tăng dần
                "columnDefs": [
                    // Cột cuối (Hành động) không cho sắp xếp
                    { "orderable": false, "targets": 3 } 
                ]
            });

            // 1. Xử lý sự kiện nhấn nút SỬA
            $(document).on('click', '.btn-edit-value', function() {
                // Lấy data từ nút
                const valueName = $(this).data('value-name');
                const updateUrl = $(this).data('update-url');

                // Điền thông tin vào modal
                $('#editValueForm').attr('action', updateUrl);
                $('#editValueName').val(valueName);
                
                // Hiển thị modal
                $('#editValueModal').modal('show');
            });

            // 2. Xử lý sự kiện nhấn nút XÓA
            $(document).on('click', '.btn-delete-value', function() {
                const valueID = $(this).data('value-id');
                const valueName = $(this).data('value-name');
                const usageCount = $(this).data('usage-count');
                
                let message = `Bạn chắc chắn muốn xóa giá trị "<strong>${valueName}</strong>"?`;
                
                if (usageCount > 0) {
                    message += `<br><br><span class="text-danger"><i class="fas fa-exclamation-triangle"></i> 
                                Cảnh báo: Có ${usageCount} biến thể đang sử dụng giá trị này!</span>`;
                }

                Swal.fire({
                    title: 'Xác nhận xóa',
                    html: message,
                    icon: usageCount > 0 ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '<i class="fas fa-trash"></i> Xóa',
                    cancelButtonText: '<i class="fas fa-times"></i> Hủy',
                    customClass: {
                        confirmButton: 'btn btn-danger mr-2',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Nếu xác nhận, submit form xóa ẩn
                        $(`#delete-form-${valueID}`).submit();
                    }
                });
            });

            // Tự động focus vào input nếu có lỗi validation khi thêm mới
            @if($errors->has('valueName'))
                $('#valueName').focus();
            @endif
        });
    </Vscript>
@endsection