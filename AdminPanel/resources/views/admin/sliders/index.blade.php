@extends('adminlte::page')

@section('title', 'Quản lý Sliders')

@section('content_header')
    <div class="row align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">
                <i class="fas fa-images text-primary"></i> Quản lý Sliders/Banners
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Sliders</li>
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

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-gradient-info shadow-sm hover-lift">
                <div class="inner">
                    <h3>{{ $sliders->total() }}</h3>
                    <p>Tổng số Sliders</p>
                </div>
                <div class="icon">
                    <i class="fas fa-images"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-gradient-success shadow-sm hover-lift">
                <div class="inner">
                    <h3>{{ $sliders->where('status', 'active')->count() }}</h3>
                    <p>Đang hoạt động</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-gradient-warning shadow-sm hover-lift">
                <div class="inner">
                    <h3>{{ $sliders->where('status', 'inactive')->count() }}</h3>
                    <p>Tạm ngưng</p>
                </div>
                <div class="icon">
                    <i class="fas fa-pause-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-gradient-primary shadow-sm hover-lift">
                <div class="inner">
                    <h3><i class="fas fa-arrows-alt"></i></h3>
                    <p>Drag để sắp xếp</p>
                </div>
                <div class="icon">
                    <i class="fas fa-sort"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card card-primary card-outline shadow">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-md-5 mb-2 mb-md-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0">
                                <i class="fas fa-search text-primary"></i>
                            </span>
                        </div>
                        <input type="text" 
                               class="form-control border-left-0 border-right-0" 
                               id="search-input" 
                               placeholder="Tìm kiếm slider...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary border-left-0" type="button" id="btn-clear-search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7 text-md-right">
                    <div class="btn-toolbar justify-content-md-end">
                        <div class="btn-group mr-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-filter">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        
                        <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Thêm Slider
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Panel --}}
        <div class="card-body border-bottom bg-light" id="filter-panel" style="display: none;">
            <form id="filter-form">
                <div class="row">
                    <div class="col-md-4">
                        <label class="small font-weight-bold">Trạng thái</label>
                        <select class="form-control form-control-sm" name="status">
                            <option value="">Tất cả</option>
                            <option value="active">✅ Hoạt động</option>
                            <option value="inactive">⛔ Tạm ngưng</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="small font-weight-bold">Sắp xếp</label>
                        <select class="form-control form-control-sm" id="sort-select">
                            <option value="displayOrder_asc">Thứ tự hiển thị</option>
                            <option value="created_at_desc">Mới nhất</option>
                            <option value="title_asc">Tên A-Z</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="small font-weight-bold">Hiển thị</label>
                        <select class="form-control form-control-sm" id="per-page-select">
                            <option value="10" selected>10 mục</option>
                            <option value="25">25 mục</option>
                            <option value="50">50 mục</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-filter">
                            <i class="fas fa-redo"></i> Đặt lại
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-search"></i> Áp dụng
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card-body position-relative" style="min-height: 400px;">
            {{-- Loading Overlay --}}
            <div class="overlay dark" id="loading-overlay" style="display: none;">
                <i class="fas fa-3x fa-sync-alt fa-spin text-primary"></i>
            </div>

            <div id="sliders-container" class="row" data-sortable="true">
                @foreach($sliders as $slider)
                    <div class="col-md-4 col-sm-6 mb-4 slider-item" data-id="{{ $slider->sliderID }}" data-order="{{ $slider->displayOrder }}">
                        <div class="card slider-card shadow-sm h-100">
                            {{-- Image --}}
                            <div class="slider-image-wrapper">
                                <img src="{{ asset('storage/' . $slider->imageUrl) }}" 
                                     class="card-img-top slider-image" 
                                     alt="{{ $slider->title }}">
                                <div class="slider-overlay">
                                    <div class="drag-handle">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                    <span class="badge badge-{{ $slider->status == 'active' ? 'success' : 'danger' }} slider-status-badge">
                                        {{ $slider->status == 'active' ? 'Hoạt động' : 'Tạm ngưng' }}
                                    </span>
                                </div>
                            </div>
                            
                            {{-- Body --}}
                            <div class="card-body">
                                <h6 class="card-title font-weight-bold">
                                    {{ $slider->title ?: 'Không có tiêu đề' }}
                                </h6>
                                
                                @if($slider->backlink)
                                    <p class="card-text small text-muted">
                                        <i class="fas fa-link"></i> 
                                        <a href="{{ $slider->backlink }}" target="_blank">
                                            {{ Str::limit($slider->backlink, 30) }}
                                        </a>
                                    </p>
                                @endif
                                
                                <p class="card-text small">
                                    <i class="fas fa-user text-muted"></i> 
                                    {{ $slider->employee->fullName ?? 'N/A' }}
                                </p>
                                
                                <p class="card-text small text-muted">
                                    <i class="fas fa-calendar"></i> 
                                    {{ $slider->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            
                            {{-- Footer --}}
                            <div class="card-footer bg-white">
                                <div class="btn-group btn-group-sm w-100">
                                    <a href="{{ route('admin.sliders.edit', $slider) }}" 
                                       class="btn btn-warning flex-fill">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    
                                    <button class="btn btn-{{ $slider->status == 'active' ? 'secondary' : 'success' }} flex-fill btn-toggle" 
                                            data-id="{{ $slider->sliderID }}"
                                            data-status="{{ $slider->status }}">
                                        <i class="fas fa-{{ $slider->status == 'active' ? 'pause' : 'play' }}"></i>
                                    </button>
                                    
                                    <button class="btn btn-danger flex-fill btn-delete" 
                                            data-id="{{ $slider->sliderID }}"
                                            data-title="{{ $slider->title ?: 'slider này' }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <div class="card-footer bg-white">
            <div class="row align-items-center">
                <div class="col-sm-5">
                    <div class="text-muted">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Hiển thị {{ $sliders->firstItem() }} - {{ $sliders->lastItem() }} 
                            trong tổng số {{ $sliders->total() }} sliders
                        </small>
                    </div>
                </div>
                <div class="col-sm-7">
                    <div class="float-sm-right">
                        {{ $sliders->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css"/>

<style>
    /* ============ ANIMATIONS ============ */
    .hover-lift {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.15) !important;
    }

    /* ============ SMALL BOX ============ */
    .small-box {
        border-radius: 10px;
        position: relative;
    }
    
    .small-box h3 {
        font-size: 2.5rem;
        font-weight: 700;
    }
    
    .small-box .icon {
        font-size: 70px;
        opacity: 0.3;
    }

    /* ============ SLIDER CARD ============ */
    .slider-card {
        border-radius: 10px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: move;
    }
    
    .slider-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
    }
    
    .slider-image-wrapper {
        position: relative;
        height: 200px;
        overflow: hidden;
        background-color: #f8f9fa;
    }
    
    .slider-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .slider-card:hover .slider-image {
        transform: scale(1.05);
    }
    
    .slider-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        padding: 10px;
        background: linear-gradient(180deg, rgba(0,0,0,0.5) 0%, transparent 100%);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    
    .drag-handle {
        background: rgba(255,255,255,0.9);
        padding: 8px 12px;
        border-radius: 5px;
        cursor: grab;
        transition: all 0.2s ease;
    }
    
    .drag-handle:hover {
        background: white;
        transform: scale(1.1);
    }
    
    .drag-handle:active {
        cursor: grabbing;
    }
    
    .slider-status-badge {
        font-size: 0.75rem;
        padding: 0.4em 0.8em;
        text-transform: uppercase;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    /* ============ SORTABLE ============ */
    .sortable-ghost {
        opacity: 0.4;
        background: #f8f9fa;
    }
    
    .sortable-chosen {
        box-shadow: 0 0 0 3px rgba(0,123,255,0.3);
    }
    
    .sortable-drag {
        opacity: 0.8;
        transform: rotate(2deg);
    }

    /* ============ BUTTONS ============ */
    .btn-group-sm .btn {
        font-size: 0.875rem;
        padding: 0.4rem 0.6rem;
    }

    /* ============ CARD BODY ============ */
    .card-body {
        padding: 1rem;
    }
    
    .card-title {
        font-size: 1rem;
        margin-bottom: 0.75rem;
        min-height: 2.4rem;
        line-height: 1.2;
    }
    
    .card-text {
        margin-bottom: 0.5rem;
    }
    
    .card-text i {
        width: 16px;
        text-align: center;
    }

    /* ============ OVERLAY ============ */
    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1050;
        border-radius: 0.25rem;
    }

    /* ============ EMPTY STATE ============ */
    .empty-state {
        padding: 3rem;
        text-align: center;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }

    /* ============ RESPONSIVE ============ */
    @media (max-width: 768px) {
        .small-box h3 {
            font-size: 2rem;
        }
        
        .slider-image-wrapper {
            height: 150px;
        }
    }
</style>
@stop

@section('js')
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
{{-- SortableJS --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
// ============================================================================
// CONSTANTS
// ============================================================================
const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
const ROUTES = {
    destroy: '/admin/sliders/:id',
    toggle: '/admin/sliders/:id/toggle-status',
    updateOrder: '/admin/sliders/update-order'
};

console.log('🎯 Slider management initialized');

// ============================================================================
// SORTABLE (DRAG & DROP)
// ============================================================================
const container = document.getElementById('sliders-container');
if (container) {
    const sortable = new Sortable(container, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        
        onEnd: function(evt) {
            console.log('🔄 Order changed');
            updateDisplayOrder();
        }
    });
}

function updateDisplayOrder() {
    const items = [];
    $('.slider-item').each(function(index) {
        items.push({
            id: $(this).data('id'),
            order: index + 1
        });
    });
    
    console.log('📤 Updating order:', items);
    
    $.ajax({
        url: ROUTES.updateOrder,
        method: 'POST',
        data: {
            _token: CSRF_TOKEN,
            orders: items
        },
        success: function(response) {
            console.log('✅ Order updated:', response);
            
            Swal.fire({
                icon: 'success',
                title: 'Đã cập nhật!',
                text: response.message,
                timer: 2000,
                showConfirmButton: false
            });
        },
        error: function(xhr) {
            console.error('❌ Update failed:', xhr);
            
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Không thể cập nhật thứ tự'
            });
            
            location.reload();
        }
    });
}

// ============================================================================
// EVENT HANDLERS
// ============================================================================
$(document).ready(function() {
    console.log('✅ Document ready');
    
    // Filter toggle
    $('#btn-filter').on('click', function() {
        $('#filter-panel').slideToggle(300);
        $(this).toggleClass('active');
    });
    
    // Refresh
    $('#btn-refresh').on('click', function() {
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        
        setTimeout(() => {
            location.reload();
        }, 500);
    });
    
    // Toggle status
    $(document).on('click', '.btn-toggle', function() {
        const sliderId = $(this).data('id');
        const currentStatus = $(this).data('status');
        
        console.log('🔄 Toggle clicked:', sliderId, currentStatus);
        
        $.ajax({
            url: ROUTES.toggle.replace(':id', sliderId),
            method: 'POST',
            data: {
                _token: CSRF_TOKEN
            },
            success: function(response) {
                console.log('✅ Toggle success:', response);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Cập nhật thành công!',
                    text: 'Trạng thái: ' + (response.status === 'active' ? 'HOẠT ĐỘNG' : 'TẠM NGƯNG'),
                    timer: 2000,
                    showConfirmButton: false
                });
                
                setTimeout(() => location.reload(), 2000);
            },
            error: function(xhr) {
                console.error('❌ Toggle failed:', xhr);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: xhr.responseJSON?.message || 'Không thể cập nhật trạng thái'
                });
            }
        });
    });
    
    // Delete
    $(document).on('click', '.btn-delete', function() {
        const sliderId = $(this).data('id');
        const sliderTitle = $(this).data('title');
        
        console.log('🗑️ Delete clicked:', sliderId, sliderTitle);
        
        Swal.fire({
            title: 'Xác nhận xóa?',
            html: `Bạn có chắc muốn xóa slider<br><strong>"${sliderTitle}"</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Xóa',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                deleteSlider(sliderId);
            }
        });
    });
    
    // Auto-dismiss alerts
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
});

function deleteSlider(id) {
    Swal.fire({
        title: 'Đang xóa...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: ROUTES.destroy.replace(':id', id),
        method: 'POST',
        data: {
            _method: 'DELETE',
            _token: CSRF_TOKEN
        },
        success: function(response) {
            console.log('✅ Deleted:', response);
            
            Swal.fire({
                icon: 'success',
                title: 'Đã xóa!',
                text: response.message || 'Slider đã được xóa',
                timer: 2000,
                showConfirmButton: false
            });
            
            setTimeout(() => location.reload(), 2000);
        },
        error: function(xhr) {
            console.error('❌ Delete failed:', xhr);
            
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: xhr.responseJSON?.message || 'Không thể xóa slider'
            });
        }
    });
}
</script>
@stop