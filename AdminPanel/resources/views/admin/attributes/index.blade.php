@extends('adminlte::page')

@section('title', 'Quản lý Thuộc tính Sản phẩm')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Quản lý Thuộc tính Sản phẩm</h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary float-right">
                <i class="fas fa-plus"></i> Thêm Thuộc tính
            </a>
        </div>
    </div>
@endsection

@section('content')
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

    {{-- DEBUG: Hiển thị số lượng categories --}}
    @if(config('app.debug'))
        <div class="alert alert-warning">
            <strong>DEBUG:</strong> Số lượng categories: {{ $categories->count() ?? 0 }}
        </div>
    @endif

    {{-- Thông tin hướng dẫn --}}
    <div class="alert alert-info alert-dismissible fade show">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h5><i class="icon fas fa-info-circle"></i> Hướng dẫn quản lý thuộc tính</h5>
        Thuộc tính giúp phân loại các biến thể của sản phẩm. 
        <strong>Lưu ý:</strong> Nên tạo thuộc tính riêng cho từng loại sản phẩm 
        (ví dụ: <code>Size Giày</code>, <code>Size Quần Áo</code>) thay vì dùng tên chung (<code>Size</code>).
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Danh sách Thuộc tính</h3>
        </div>
        <div class="card-body">
            <table id="attributesTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th style="width: 5%">ID</th>
                        <th style="width: 20%">Tên Thuộc tính</th>
                        <th style="width: 15%">Số giá trị</th>
                        <th style="width: 30%">Dùng cho danh mục</th>
                        <th style="width: 30%">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attributes as $attribute)
                        <tr>
                            <td>{{ $attribute->attributeID }}</td>
                            <td>
                                <strong>{{ $attribute->attributeName }}</strong>
                                @php
                                    $usageCount = DB::table('variant_attribute_values as vav')
                                        ->join('product_attribute_values as pav', 'vav.valueID', '=', 'pav.valueID')
                                        ->where('pav.attributeID', $attribute->attributeID)
                                        ->distinct('vav.variantID')
                                        ->count('vav.variantID');
                                @endphp
                                @if($usageCount > 0)
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-box"></i> 
                                        Đang dùng: {{ $usageCount }} biến thể
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info badge-lg">
                                    {{ count($attribute->values) }} giá trị
                                </span>
                            </td>
                            <td>
                                @php
                                    // FIX: Đổi tên biến để không ghi đè $categories từ controller
                                    $assignedCategories = DB::table('category_attributes as ca')
                                        ->join('categories as c', 'ca.categoryID', '=', 'c.categoryID')
                                        ->where('ca.attributeID', $attribute->attributeID)
                                        ->select('c.categoryName')
                                        ->get();
                                @endphp
                                
                                @if($assignedCategories->count() > 0)
                                    @foreach($assignedCategories as $cat)
                                        <span class="badge badge-secondary">
                                            {{ $cat->categoryName }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-muted">
                                        <i class="fas fa-minus-circle"></i> Chưa gán
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    {{-- Nút Gán danh mục --}}
                                    <button type="button" 
                                            class="btn btn-success btn-assign-category" 
                                            data-attribute-id="{{ $attribute->attributeID }}"
                                            data-attribute-name="{{ $attribute->attributeName }}"
                                            title="Gán danh mục">
                                        <i class="fas fa-link"></i> Gán DM
                                    </button>
                                    
                                    {{-- Nút Giá trị --}}
                                    <a href="{{ route('admin.attributes.values.index', $attribute->attributeID) }}" 
                                       class="btn btn-info" 
                                       title="Quản lý giá trị">
                                        <i class="fas fa-list"></i> Giá trị
                                    </a>
                                    
                                    {{-- Nút Sửa --}}
                                    <a href="{{ route('admin.attributes.edit', $attribute->attributeID) }}" 
                                       class="btn btn-warning" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    
                                    {{-- Nút Xóa --}}
                                    <button type="button" 
                                            class="btn btn-danger btn-delete-attribute" 
                                            data-attribute-id="{{ $attribute->attributeID }}"
                                            data-attribute-name="{{ $attribute->attributeName }}"
                                            data-usage-count="{{ $usageCount }}"
                                            title="Xóa">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </div>
                                
                                <form id="delete-form-{{ $attribute->attributeID }}" 
                                      action="{{ route('admin.attributes.destroy', $attribute->attributeID) }}" 
                                      method="POST" 
                                      style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Chưa có thuộc tính nào</p>
                                <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tạo thuộc tính đầu tiên
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Gán Danh mục --}}
    <div class="modal fade" id="assignCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="assignCategoryForm" method="POST">
                    @csrf
                    
                    <div class="modal-header bg-success">
                        <h5 class="modal-title">
                            <i class="fas fa-link"></i> 
                            Gán Danh mục cho: <span id="modalAttributeName" class="font-weight-bold"></span>
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Hướng dẫn:</strong> Chọn các danh mục mà thuộc tính này sẽ áp dụng. 
                            Ví dụ: "Size Giày" nên gán cho danh mục "Giày cầu lông".
                        </div>

                        <input type="hidden" id="modalAttributeID" name="attributeID">

                        <div class="form-group">
                            <label><strong>Chọn danh mục:</strong></label>
                            
                            {{-- FIX: Biến $categories từ controller KHÔNG bị ghi đè nữa --}}
                            @if(isset($categories) && $categories->count() > 0)
                                <div id="categoriesContainer">
                                    @foreach($categories as $category)
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" 
                                                   class="custom-control-input category-checkbox" 
                                                   id="category-{{ $category->categoryID }}" 
                                                   name="categories[]" 
                                                   value="{{ $category->categoryID }}">
                                            <label class="custom-control-label" for="category-{{ $category->categoryID }}">
                                                <strong>{{ $category->categoryName }}</strong>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Không có danh mục nào. Vui lòng tạo danh mục trước.
                                </div>
                            @endif
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lưu ý:</strong> Nếu bỏ chọn danh mục, các liên kết hiện tại sẽ bị xóa.
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Lưu gán
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

{{-- CSS --}}
@section('css')
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">

<style>
    .badge-lg {
        font-size: 0.95rem;
        padding: 0.4em 0.6em;
    }
    
    .btn-group .btn {
        margin-right: 2px;
    }
    
    .table td {
        vertical-align: middle;
    }

    .custom-control-label {
        cursor: pointer;
        padding-top: 2px;
    }

    .swal2-actions {
        gap: 10px;
    }

    #categoriesContainer .custom-control {
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    #categoriesContainer .custom-control:hover {
        background-color: #f8f9fa;
    }
</style>
@endsection

{{-- JavaScript --}}
@section('js')
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    console.log('=== CHECKING LIBRARIES ===');
    console.log('jQuery:', typeof $, $.fn.jquery);
    console.log('DataTable:', typeof $.fn.DataTable);
    console.log('Bootstrap modal:', typeof $.fn.modal);
    console.log('Swal:', typeof Swal);
    console.log('==========================');

    $(document).ready(function() {
        console.log('Document ready!');
        
        // Khởi tạo DataTable
        try {
            var table = $('#attributesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json"
                },
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": 4 }
                ],
                "order": [[0, 'asc']]
            });
            console.log('✅ DataTable initialized successfully!');
        } catch(e) {
            console.error('❌ DataTable initialization failed:', e);
        }

        // Event delegation cho nút Gán DM
        $(document).on('click', '.btn-assign-category', function() {
            console.log('🔘 Assign button clicked!');
            const attributeID = $(this).data('attribute-id');
            const attributeName = $(this).data('attribute-name');
            console.log('Attribute:', attributeID, attributeName);
            openAssignModal(attributeID, attributeName);
        });

        // Event delegation cho nút Xóa
        $(document).on('click', '.btn-delete-attribute', function() {
            console.log('🗑️ Delete button clicked!');
            const attributeID = $(this).data('attribute-id');
            const attributeName = $(this).data('attribute-name');
            const usageCount = $(this).data('usage-count');
            console.log('Delete:', attributeID, attributeName, 'Usage:', usageCount);
            confirmDelete(attributeID, attributeName, usageCount);
        });
    });

    // Hàm mở modal gán danh mục
    function openAssignModal(attributeID, attributeName) {
        console.log('📂 Opening assign modal for attribute:', attributeID, attributeName);
        
        $('#modalAttributeID').val(attributeID);
        $('#modalAttributeName').text(attributeName);
        
        const actionUrl = `/admin/attributes/${attributeID}/assign-categories`;
        $('#assignCategoryForm').attr('action', actionUrl);
        console.log('Form action set to:', actionUrl);
        
        // Uncheck tất cả checkbox
        $('.category-checkbox').prop('checked', false);
        console.log('All checkboxes unchecked');
        
        // Load danh mục hiện tại đã được gán
        const getUrl = `/admin/attributes/${attributeID}/categories`;
        console.log('Fetching assigned categories from:', getUrl);
        
        $.ajax({
            url: getUrl,
            method: 'GET',
            success: function(data) {
                console.log('✅ Assigned categories loaded:', data);
                data.forEach(function(categoryID) {
                    const checkboxId = `#category-${categoryID}`;
                    $(checkboxId).prop('checked', true);
                    console.log('Checked category:', categoryID);
                });
                
                // Hiển thị modal
                console.log('Showing modal...');
                $('#assignCategoryModal').modal('show');
                console.log('✅ Modal shown successfully!');
            },
            error: function(xhr, status, error) {
                console.error('❌ Error loading assigned categories:', error);
                console.error('XHR:', xhr);
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Không thể tải danh mục: ' + error
                });
            }
        });
    }

    // Submit form gán danh mục
    $('#assignCategoryForm').on('submit', function(e) {
        e.preventDefault();
        console.log('📤 Form submitted!');
        
        const form = $(this);
        const url = form.attr('action');
        const data = form.serialize();
        
        console.log('Submitting to:', url);
        console.log('Data:', data);
        
        Swal.fire({
            title: 'Đang xử lý...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                console.log('✅ Success response:', response);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: response.message || 'Đã gán danh mục thành công',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Đóng modal
                $('#assignCategoryModal').modal('hide');
                
                // Reload trang sau 2 giây
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            },
            error: function(xhr, status, error) {
                console.error('❌ Error response:', xhr.responseJSON);
                console.error('Status:', status);
                console.error('Error:', error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: xhr.responseJSON?.message || 'Có lỗi xảy ra: ' + error
                });
            }
        });
    });

    // Hàm confirm delete
    function confirmDelete(attributeID, attributeName, usageCount) {
        console.log('🗑️ Confirming delete:', attributeID, attributeName, usageCount);
        
        let message = `Bạn chắc chắn muốn xóa thuộc tính "<strong>${attributeName}</strong>"?`;
        
        if (usageCount > 0) {
            message += `<br><br><span class="text-danger"><i class="fas fa-exclamation-triangle"></i> 
                        Cảnh báo: Có ${usageCount} biến thể đang sử dụng!</span>`;
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
            console.log('Swal result:', result);
            if (result.isConfirmed) {
                console.log('✅ Delete confirmed, submitting form');
                const formId = 'delete-form-' + attributeID;
                console.log('Form ID:', formId);
                document.getElementById(formId).submit();
            } else {
                console.log('❌ Delete cancelled');
            }
        });
    }
</script>
@endsection