@extends('adminlte::page')

@section('title', 'Quản lý Danh mục')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-sitemap"></i> Quản lý Danh mục
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Danh mục</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">

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

            {{-- Statistics Cards --}}
            <div class="row mb-3">
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $totalCategoryCount }}</h3>
                            <p>Tổng danh mục</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-sitemap"></i>
                        </div>
                        <a href="{{ route('admin.categories.index', ['status' => 'all']) }}" class="small-box-footer">
                            Xem tất cả <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $activeCategoryCount }}</h3>
                            <p>Đang hiển thị</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <a href="{{ route('admin.categories.index', ['status' => 'active']) }}" class="small-box-footer">
                            Chi tiết <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $inactiveCategoryCount }}</h3>
                            <p>Đã ẩn</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-eye-slash"></i>
                        </div>
                        <a href="{{ route('admin.categories.index', ['status' => 'inactive']) }}" class="small-box-footer">
                            Chi tiết <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Main Card --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list"></i> 
                        @if($status == 'active')
                            Danh mục Đang hiển thị
                        @elseif($status == 'inactive')
                            Danh mục Đã ẩn
                        @else
                            Tất cả Danh mục
                        @endif
                    </h3>
                    <div class="card-tools">
                        {{-- Filter Buttons --}}
                        <div class="btn-group mr-2">
                            <a href="{{ route('admin.categories.index', ['status' => 'active']) }}" 
                               class="btn btn-sm {{ $status == 'active' ? 'btn-success' : 'btn-default' }}"
                               title="Đang hiển thị">
                                <i class="fas fa-eye"></i> Hiển thị
                            </a>
                            <a href="{{ route('admin.categories.index', ['status' => 'inactive']) }}" 
                               class="btn btn-sm {{ $status == 'inactive' ? 'btn-warning' : 'btn-default' }}"
                               title="Đã ẩn">
                                <i class="fas fa-eye-slash"></i> Đã ẩn
                            </a>
                            <a href="{{ route('admin.categories.index', ['status' => 'all']) }}" 
                               class="btn btn-sm {{ $status == 'all' ? 'btn-secondary' : 'btn-default' }}"
                               title="Tất cả">
                                <i class="fas fa-list"></i> Tất cả
                            </a>
                        </div>
                        
                        <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Thêm mới
                        </a>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="categoriesTable" class="table table-hover table-striped">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Tên Danh mục</th>
                                    <th>Số SP (Active)</th>
                                    <th style="width: 150px" class="text-center">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $category)
                                    <tr class="{{ $category->is_active ? '' : 'table-secondary' }}">
                                        <td>{{ $category->categoryID }}</td>
                                        <td>
                                            <strong>{{ $category->categoryName }}</strong>
                                            @if (!$category->is_active)
                                                <span class="badge badge-warning ml-2">
                                                    <i class="fas fa-eye-slash"></i> Đã ẩn
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ $category->products->count() }} SP
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                {{-- Nút Sửa --}}
                                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-info" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                @if ($category->is_active)
                                                    {{-- Nút ẨN (KHÓA) --}}
                                                    <button type="button" 
                                                            class="btn btn-warning btn-lock-category" 
                                                            data-category-id="{{ $category->categoryID }}"
                                                            data-category-name="{{ $category->categoryName }}"
                                                            title="Ẩn danh mục">
                                                        <i class="fas fa-eye-slash"></i>
                                                    </button>
                                                @else
                                                    {{-- Nút HIỂN THỊ LẠI (MỞ KHÓA) --}}
                                                    <button type="button" 
                                                            class="btn btn-success btn-activate-category" 
                                                            data-category-id="{{ $category->categoryID }}"
                                                            data-category-name="{{ $category->categoryName }}"
                                                            title="Hiển thị lại">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                @endif
                                            </div>

                                            {{-- Form ẩn để ẨN (Soft Delete) --}}
                                            <form id="lock-form-{{ $category->categoryID }}" 
                                                  action="{{ route('admin.categories.destroy', $category) }}" 
                                                  method="POST" 
                                                  style="display:none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            
                                            {{-- Form ẩn để MỞ KHÓA (Re-activate) --}}
                                            <form id="activate-form-{{ $category->categoryID }}" 
                                                  action="{{ route('admin.categories.update', $category) }}" 
                                                  method="POST" 
                                                  style="display:none;">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="action_reactivate" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>Không có danh mục nào</p>
                                            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Tạo danh mục
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                @if($categories->hasPages())
                <div class="card-footer clearfix">
                    <div class="float-left">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Hiển thị {{ $categories->firstItem() }} - {{ $categories->lastItem() }} 
                            trong tổng số <strong>{{ $categories->total() }}</strong> danh mục
                        </small>
                    </div>
                    <div class="float-right">
                        {{ $categories->appends(['status' => $status])->links('pagination::bootstrap-4') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .small-box h3 { font-size: 2.2rem; }
    .table-hover tbody tr:hover { background-color: #f8f9fa !important; }
    .table-secondary { opacity: 0.7; }
    .table-secondary:hover { opacity: 1; }
    .badge { font-size: 0.85em; padding: 0.3em 0.6em; }
    .btn-group-sm > .btn { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
    .card-outline { border-top: 3px solid #007bff; }
</style>
@stop

@section('js')
{{-- ⚠️ SỬA LỖI 1: URL CDN của SweetAlert2 bị sai (cdn.jsdelivr-npm thay vì cdn.jsdelivr.net) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ⚠️ SỬA LỖI 2: Đảm bảo jQuery đã load xong
document.addEventListener('DOMContentLoaded', function() {
    
    // Kiểm tra jQuery có sẵn không
    if (typeof jQuery === 'undefined') {
        console.error('jQuery chưa được load!');
        return;
    }

    console.log('Script categories đã load thành công');

    // ========================================================================
    // ẨN DANH MỤC (SOFT DELETE)
    // ========================================================================
    $(document).on('click', '.btn-lock-category', function(e) {
        e.preventDefault();
        console.log('Nút ẨN được click');
        
        const categoryId = $(this).data('category-id');
        const categoryName = $(this).data('category-name');
        
        console.log('Category ID:', categoryId);
        console.log('Category Name:', categoryName);
        
        Swal.fire({
            title: 'Xác nhận ẨN danh mục?',
            html: `Bạn có chắc chắn muốn ẩn danh mục<br><strong>"${categoryName}"</strong>?<br><br>
                   <small class="text-muted">Danh mục sẽ bị ẩn khỏi trang bán hàng.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-eye-slash"></i> Ẩn',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Đang submit form ẩn...');
                document.getElementById(`lock-form-${categoryId}`).submit();
            }
        });
    });
    
    // ========================================================================
    // HIỂN THỊ LẠI DANH MỤC (RE-ACTIVATE)
    // ========================================================================
    $(document).on('click', '.btn-activate-category', function(e) {
        e.preventDefault();
        console.log('Nút HIỂN THỊ được click');
        
        const categoryId = $(this).data('category-id');
        const categoryName = $(this).data('category-name');
        
        console.log('Category ID:', categoryId);
        console.log('Category Name:', categoryName);
        
        Swal.fire({
            title: 'Xác nhận HIỂN THỊ?',
            html: `Bạn có chắc chắn muốn hiển thị lại danh mục<br><strong>"${categoryName}"</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-eye"></i> Hiển thị',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Đang submit form hiển thị...');
                document.getElementById(`activate-form-${categoryId}`).submit();
            }
        });
    });
    
    // Tự động ẩn Alert sau 5 giây
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@stop
