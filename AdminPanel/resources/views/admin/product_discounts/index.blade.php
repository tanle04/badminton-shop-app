{{-- resources/views/admin/product_discounts/index.blade.php --}}
@extends('adminlte::page')

@section('title', 'Quản lý Chương trình Giảm giá Sản phẩm')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-percentage"></i> Chương trình Giảm giá Sản phẩm
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Giảm giá</li>
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
    <div class="row mb-3">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="stat-total">-</h3>
                    <p>Tổng chương trình</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tags"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="stat-active">-</h3>
                    <p>Đang hoạt động</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="stat-expired">-</h3>
                    <p>Đã hết hạn</p>
                </div>
                <div class="icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="stat-inactive">-</h3>
                    <p>Tạm ngưng</p>
                </div>
                <div class="icon">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> Danh sách Chương trình Sale
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" id="btn-refresh" title="Làm mới">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <a href="{{ route('admin.product-discounts.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tạo Sale mới
                </a>
            </div>
        </div>
        
        <div class="card-body">
            {{-- Loading Overlay --}}
            <div class="overlay" id="loading-overlay" style="display: none;">
                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
            </div>

            <div class="table-responsive">
                <table id="discountsTable" class="table table-hover table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 5%">ID</th>
                            <th style="width: 20%">Tên chương trình</th>
                            <th style="width: 15%">Giá trị/Loại</th>
                            <th style="width: 15%">Áp dụng cho</th>
                            <th style="width: 20%">Thời gian</th>
                            <th style="width: 10%">Trạng thái</th>
                            <th style="width: 15%">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="loading-row">
                            <td colspan="7" class="text-center">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Đang tải dữ liệu...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer clearfix">
            <div id="pagination-info" class="float-left">
                <small class="text-muted">Đang tải...</small>
            </div>
            <div id="pagination-links" class="float-right">
                {{-- Pagination sẽ được render bởi JS --}}
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
    
    .badge-status {
        font-size: 0.85rem;
        padding: 0.4em 0.6em;
    }
    
    .btn-xs {
        padding: 0.25rem 0.4rem;
        font-size: 0.75rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
    
    .discount-value {
        font-weight: 600;
        color: #007bff;
    }
    
    .discount-max {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .applied-to {
        display: inline-block;
        padding: 0.2em 0.6em;
        background: #e9ecef;
        border-radius: 3px;
        font-size: 0.85rem;
    }
    
    .date-range {
        font-size: 0.9rem;
        white-space: nowrap;
    }
    
    .small-box h3 {
        font-size: 2.2rem;
    }
    
    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
</style>
@stop

@section('js')
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ============================================================================
// CONSTANTS
// ============================================================================
const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
const ROUTES = {
    apiIndex: '{{ route("admin.product-discounts.apiIndex") }}',
    edit: '/admin/product-discounts/:id/edit',
    delete: '/admin/product-discounts/:id',
    toggle: '/admin/product-discounts/:id/toggle-active'
};

console.log('🎯 Routes configured:', ROUTES);
console.log('⏰ Timezone: Asia/Ho_Chi_Minh (UTC+7)');

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('vi-VN', { 
        style: 'currency', 
        currency: 'VND' 
    }).format(amount);
};

/**
 * ⭐ SỬA HÀM formatDate - Hiển thị đúng múi giờ Việt Nam
 */
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    
    // Parse date từ backend (đã là Asia/Ho_Chi_Minh)
    const date = new Date(dateString);
    
    // Format theo múi giờ Việt Nam
    return date.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'Asia/Ho_Chi_Minh'
    });
};

/**
 * ⭐ SỬA HÀM getStatusBadge - So sánh với thời gian Việt Nam
 */
const getStatusBadge = (discount) => {
    // Lấy thời gian hiện tại (JS tự động dùng timezone của máy)
    const now = new Date();
    const startDate = new Date(discount.startDate);
    const endDate = new Date(discount.endDate);
    
    // 1. KIỂM TRA TRẠNG THÁI TẮT (isActive = 0)
    if (!discount.isActive) {
        return '<span class="badge badge-danger badge-status"><i class="fas fa-ban"></i> Tạm ngưng</span>';
    }
    
    // 2. KIỂM TRA HẾT HẠN (endDate < now)
    if (endDate < now) {
        return '<span class="badge badge-warning badge-status"><i class="fas fa-calendar-times"></i> Đã hết hạn</span>';
    }
    
    // 3. KIỂM TRA CHƯA BẮT ĐẦU (startDate > now)
    if (startDate > now) {
        return '<span class="badge badge-info badge-status"><i class="fas fa-hourglass-start"></i> Chưa bắt đầu</span>';
    }
    
    // 4. ĐANG HOẠT ĐỘNG (isActive = 1 và trong khoảng thời gian)
    return '<span class="badge badge-success badge-status"><i class="fas fa-check-circle"></i> Đang hoạt động</span>';
};

const getValueDisplay = (discount) => {
    let html = '<div class="discount-value">';
    
    if (discount.discountType === 'percentage') {
        html += `<i class="fas fa-percent"></i> ${discount.discountValue}%`;
        if (discount.maxDiscountAmount) {
            html += `<div class="discount-max">Tối đa: ${formatCurrency(discount.maxDiscountAmount)}</div>`;
        }
    } else {
        html += `<i class="fas fa-dollar-sign"></i> ${formatCurrency(discount.discountValue)}`;
    }
    
    html += '</div>';
    return html;
};

const getAppliedToDisplay = (discount) => {
    const typeLabels = {
        'category': 'Danh mục',
        'brand': 'Thương hiệu',
        'product': 'Sản phẩm',
        'variant': 'Biến thể'
    };
    
    const typeIcons = {
        'category': 'fas fa-folder',
        'brand': 'fas fa-copyright',
        'product': 'fas fa-box',
        'variant': 'fas fa-boxes'
    };
    
    return `<span class="applied-to">
                <i class="${typeIcons[discount.appliedToType]}"></i>
                ${typeLabels[discount.appliedToType]} #${discount.appliedToID}
            </span>`;
};

// ============================================================================
// MAIN FUNCTIONS
// ============================================================================
/**
 * ⭐ SỬA HÀM updateStats - Thống kê chính xác hơn
 */
function updateStats(data) {
    let total = data.length;
    let active = 0;      // Đang hoạt động (isActive=1 và trong thời gian)
    let expired = 0;     // Đã hết hạn (endDate < now)
    let inactive = 0;    // Tạm ngưng (isActive=0)
    let upcoming = 0;    // Chưa bắt đầu (startDate > now)
    
    const now = new Date();
    
    data.forEach(discount => {
        const startDate = new Date(discount.startDate);
        const endDate = new Date(discount.endDate);
        
        if (!discount.isActive) {
            // Tạm ngưng
            inactive++;
        } else if (endDate < now) {
            // Đã hết hạn
            expired++;
        } else if (startDate > now) {
            // Chưa bắt đầu (có thể tính vào active hoặc tạo stat riêng)
            upcoming++;
            active++; // Hoặc không tính vào active
        } else {
            // Đang hoạt động
            active++;
        }
    });
    
    $('#stat-total').text(total);
    $('#stat-active').text(active);
    $('#stat-expired').text(expired);
    $('#stat-inactive').text(inactive);
    
    console.log('📊 Stats:', {total, active, expired, inactive, upcoming});
}

function renderDiscounts(data) {
    console.log('📊 Rendering', data.length, 'discounts');
    
    if (data.length === 0) {
        $('#discountsTable tbody').html(`
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Chưa có chương trình giảm giá nào</p>
                    <a href="{{ route('admin.product-discounts.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo chương trình đầu tiên
                    </a>
                </td>
            </tr>
        `);
        return;
    }
    
    let rows = '';
    
    data.forEach(discount => {
        const editUrl = ROUTES.edit.replace(':id', discount.discountID);
        
        rows += `
        <tr data-id="${discount.discountID}">
            <td class="text-center"><strong>#${discount.discountID}</strong></td>
            <td>
                <strong>${discount.discountName}</strong>
                <br>
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Tạo: ${formatDate(discount.created_at)}
                </small>
            </td>
            <td>${getValueDisplay(discount)}</td>
            <td>${getAppliedToDisplay(discount)}</td>
            <td class="date-range">
                <i class="fas fa-calendar-alt text-success"></i> ${formatDate(discount.startDate)}
                <br>
                <i class="fas fa-calendar-times text-danger"></i> ${formatDate(discount.endDate)}
            </td>
            <td class="text-center">${getStatusBadge(discount)}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="${editUrl}" 
                       class="btn btn-warning btn-xs" 
                       title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </a>
                    
                    <button class="btn ${discount.isActive ? 'btn-secondary' : 'btn-success'} btn-xs btn-toggle" 
                            data-id="${discount.discountID}" 
                            data-status="${discount.isActive ? 1 : 0}"
                            title="${discount.isActive ? 'Tắt' : 'Bật'}">
                        <i class="fas ${discount.isActive ? 'fa-toggle-off' : 'fa-toggle-on'}"></i>
                    </button>
                    
                    <button class="btn btn-danger btn-xs btn-delete" 
                            data-id="${discount.discountID}"
                            data-name="${discount.discountName}"
                            title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        `;
    });
    
    $('#discountsTable tbody').html(rows);
    $('#pagination-info').html(`
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> Hiển thị <strong>${data.length}</strong> chương trình
        </small>
    `);
}

function loadDiscounts() {
    console.log('🔄 Loading discounts...');
    
    $('#loading-overlay').show();
    
    $.ajax({
        url: ROUTES.apiIndex,
        method: 'GET',
        success: function(response) {
            console.log('✅ Data loaded:', response);
            
            renderDiscounts(response.data);
            updateStats(response.data);
            
            $('#loading-overlay').hide();
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading data:', error, xhr);
            
            $('#loading-overlay').hide();
            
            $('#discountsTable tbody').html(`
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p><strong>Lỗi tải dữ liệu!</strong></p>
                        <p>${xhr.responseJSON?.message || error}</p>
                        <button class="btn btn-primary" onclick="loadDiscounts()">
                            <i class="fas fa-sync"></i> Thử lại
                        </button>
                    </td>
                </tr>
            `);
        }
    });
}

// ============================================================================
// EVENT HANDLERS
// ============================================================================
$(document).ready(function() {
    console.log('✅ Document ready');
    console.log('⏰ Client timezone:', Intl.DateTimeFormat().resolvedOptions().timeZone);
    
    // Load initial data
    loadDiscounts();
    
    // Refresh button
    $('#btn-refresh').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadDiscounts();
        setTimeout(() => {
            $(this).find('i').removeClass('fa-spin');
        }, 1000);
    });
    
    // Delete handler
    $(document).on('click', '.btn-delete', function() {
        const discountId = $(this).data('id');
        const discountName = $(this).data('name');
        
        console.log('🗑️ Delete clicked:', discountId, discountName);
        
        Swal.fire({
            title: 'Xác nhận xóa?',
            html: `Bạn có chắc chắn muốn xóa chương trình<br><strong>"${discountName}"</strong>?`,
            icon: 'warning',
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
                deleteDiscount(discountId);
            }
        });
    });
    
    // Toggle handler
    $(document).on('click', '.btn-toggle', function() {
        const discountId = $(this).data('id');
        const currentStatus = $(this).data('status');
        
        console.log('🔄 Toggle clicked:', discountId, 'Current:', currentStatus);
        
        toggleDiscount(discountId);
    });
});

function deleteDiscount(id) {
    console.log('🗑️ Deleting discount:', id);
    
    Swal.fire({
        title: 'Đang xóa...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: ROUTES.delete.replace(':id', id),
        method: 'POST',
        data: {
            _method: 'DELETE',
            _token: CSRF_TOKEN
        },
        success: function(response) {
            console.log('✅ Deleted successfully:', response);
            
            Swal.fire({
                icon: 'success',
                title: 'Đã xóa!',
                text: response.message || 'Chương trình đã được xóa thành công',
                timer: 2000,
                showConfirmButton: false
            });
            
            loadDiscounts();
        },
        error: function(xhr) {
            console.error('❌ Delete failed:', xhr);
            
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: xhr.responseJSON?.message || 'Không thể xóa chương trình',
            });
        }
    });
}

function toggleDiscount(id) {
    console.log('🔄 Toggling discount:', id);
    
    $.ajax({
        url: ROUTES.toggle.replace(':id', id),
        method: 'POST',
        data: {
            _method: 'PUT',
            _token: CSRF_TOKEN
        },
        success: function(response) {
            console.log('✅ Toggled successfully:', response);
            
            const statusText = response.isActive ? 'HOẠT ĐỘNG' : 'TẠM NGƯNG';
            const icon = response.isActive ? 'success' : 'info';
            
            Swal.fire({
                icon: icon,
                title: 'Cập nhật thành công!',
                text: `Trạng thái: ${statusText}`,
                timer: 2000,
                showConfirmButton: false
            });
            
            loadDiscounts();
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
}
</script>
@stop