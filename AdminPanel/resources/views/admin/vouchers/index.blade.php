@extends('adminlte::page')

@section('title', 'Quản lý Mã giảm giá (Vouchers)')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-ticket-alt"></i> Quản lý Mã giảm giá (Vouchers)
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Mã giảm giá</li>
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
                    <p>Tổng số Vouchers</p>
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
                    <i class="fas fa-clock"></i>
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
                <i class="fas fa-list"></i> Danh sách Mã giảm giá
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" id="btn-refresh" title="Làm mới">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tạo Voucher
                </a>
            </div>
        </div>
        
        <div class="card-body">
            {{-- Loading Overlay --}}
            <div class="overlay" id="loading-overlay" style="display: none;">
                <i class="fas fa-3x fa-sync-alt fa-spin"></i>
            </div>

            <div class="table-responsive">
                <table id="vouchersTable" class="table table-hover table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 15%">Mã Code</th>
                            <th style="width: 20%">Tên Voucher</th>
                            <th style="width: 20%">Giá trị/Loại</th>
                            <th style="width: 15%">Sử dụng (Đã dùng/Max)</th>
                            <th style="width: 15%">Thời gian</th>
                            <th style="width: 10%">Trạng thái</th>
                            <th style="width: 10%">Hành động</th>
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
    
    .voucher-code {
        font-weight: 700;
        color: #d90f23;
        font-size: 1.1rem;
        background: #fdf0f1;
        padding: 0.2em 0.5em;
        border-radius: 4px;
        border: 1px dashed #f5c6cb;
    }

    .discount-value {
        font-weight: 600;
        color: #007bff;
    }
    
    .discount-max {
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .min-order {
        font-size: 0.85rem;
        color: #28a745;
    }

    .usage-count {
        font-size: 0.9rem;
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
    // ❗ QUAN TRỌNG: Bạn cần tạo route 'admin.vouchers.apiIndex' trong web.php
    apiIndex: '{{ route("admin.vouchers.apiIndex") }}', 
    edit: '/admin/vouchers/:id/edit',
    delete: '/admin/vouchers/:id',
    // ❗ QUAN TRỌNG: Bạn cần tạo route 'admin.vouchers.toggleActive' trong web.php
    toggle: '/admin/vouchers/:id/toggle-active' 
};

console.log('🎯 Routes configured:', ROUTES);

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('vi-VN', { 
        style: 'currency', 
        currency: 'VND' 
    }).format(amount);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const getStatusBadge = (voucher) => {
    const today = new Date();
    const endDate = new Date(voucher.endDate);
    const startDate = new Date(voucher.startDate);
    
    if (!voucher.isActive) {
        return '<span class="badge badge-danger badge-status"><i class="fas fa-ban"></i> Tạm ngưng</span>';
    } else if (endDate < today) {
        return '<span class="badge badge-warning badge-status"><i class="fas fa-clock"></i> Hết hạn</span>';
    } else if (startDate > today) {
        return '<span class="badge badge-info badge-status"><i class="fas fa-calendar-alt"></i> Sắp diễn ra</span>';
    } else {
        return '<span class="badge badge-success badge-status"><i class="fas fa-check-circle"></i> Hoạt động</span>';
    }
};

const getValueDisplay = (voucher) => {
    let html = '<div class="discount-value">';
    
    if (voucher.discountType === 'percentage') {
        html += `<i class="fas fa-percent"></i> ${voucher.discountValue}%`;
        if (voucher.maxDiscountAmount) {
            html += `<div class="discount-max">Tối đa: ${formatCurrency(voucher.maxDiscountAmount)}</div>`;
        }
    } else {
        html += `<i class="fas fa-dollar-sign"></i> ${formatCurrency(voucher.discountValue)}`;
    }
    
    html += `</div>`;
    html += `<div class="min-order">Đơn tối thiểu: ${formatCurrency(voucher.minOrderValue)}</div>`;
    return html;
};

const getUsageDisplay = (voucher) => {
    return `<span class="usage-count">
                <strong>${voucher.usedCount}</strong> / ${voucher.maxUsage}
            </span>`;
};

// ============================================================================
// MAIN FUNCTIONS
// ============================================================================
function updateStats(data) {
    let total = data.length;
    let active = 0;
    let expired = 0;
    let inactive = 0;
    
    const today = new Date();
    
    data.forEach(voucher => {
        const endDate = new Date(voucher.endDate);
        const startDate = new Date(voucher.startDate);
        
        if (!voucher.isActive) {
            inactive++;
        } else if (endDate < today) {
            expired++;
        } else if (startDate <= today) {
            active++;
        }
    });
    
    $('#stat-total').text(total);
    $('#stat-active').text(active);
    $('#stat-expired').text(expired);
    $('#stat-inactive').text(inactive);
}

function renderVouchers(data) {
    console.log('📊 Rendering', data.length, 'vouchers');
    
    if (data.length === 0) {
        $('#vouchersTable tbody').html(`
            <tr>
                <td colspan="7" class="text-center text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Chưa có mã giảm giá nào</p>
                    <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo voucher đầu tiên
                    </a>
                </td>
            </tr>
        `);
        return;
    }
    
    let rows = '';
    
    data.forEach(voucher => {
        const editUrl = ROUTES.edit.replace(':id', voucher.voucherID);
        
        rows += `
        <tr data-id="${voucher.voucherID}">
            <td><span class="voucher-code">${voucher.voucherCode}</span></td>
            <td>
                <strong>${voucher.voucherName}</strong>
                <br>
                <small class="text-muted">
                    <i class="fas fa-calendar"></i> Tạo: ${formatDate(voucher.created_at)}
                </small>
            </td>
            <td>${getValueDisplay(voucher)}</td>
            <td class="text-center">${getUsageDisplay(voucher)}</td>
            <td class="date-range">
                <i class="fas fa-calendar-alt text-success"></i> ${formatDate(voucher.startDate)}
                <br>
                <i class="fas fa-calendar-times text-danger"></i> ${formatDate(voucher.endDate)}
            </td>
            <td class="text-center">${getStatusBadge(voucher)}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="${editUrl}" 
                       class="btn btn-warning btn-xs" 
                       title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </a>
                    
                    <button class="btn ${voucher.isActive ? 'btn-secondary' : 'btn-success'} btn-xs btn-toggle" 
                            data-id="${voucher.voucherID}" 
                            data-status="${voucher.isActive ? 1 : 0}"
                            title="${voucher.isActive ? 'Tắt' : 'Bật'}">
                        <i class="fas ${voucher.isActive ? 'fa-toggle-off' : 'fa-toggle-on'}"></i>
                    </button>
                    
                    <button class="btn btn-danger btn-xs btn-delete" 
                            data-id="${voucher.voucherID}"
                            data-name="${voucher.voucherCode}"
                            title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        `;
    });
    
    $('#vouchersTable tbody').html(rows);
    $('#pagination-info').html(`
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> Hiển thị <strong>${data.length}</strong> vouchers
        </small>
    `);
}

function loadVouchers() {
    console.log('🔄 Loading vouchers...');
    
    $('#loading-overlay').show();
    
    $.ajax({
        url: ROUTES.apiIndex,
        method: 'GET',
        success: function(response) {
            console.log('✅ Data loaded:', response);
            
            // Giả sử API của bạn trả về { data: [...] }
            // Nếu API chỉ trả về [...], hãy dùng:
            // renderVouchers(response);
            // updateStats(response);

            renderVouchers(response.data);
            updateStats(response.data);
            
            $('#loading-overlay').hide();
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading data:', error, xhr);
            
            $('#loading-overlay').hide();
            
            $('#vouchersTable tbody').html(`
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p><strong>Lỗi tải dữ liệu!</strong></p>
                        <p>${xhr.responseJSON?.message || error}</p>
                        <button class="btn btn-primary" onclick="loadVouchers()">
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
    
    // Load initial data
    loadVouchers();
    
    // Refresh button
    $('#btn-refresh').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadVouchers();
        setTimeout(() => {
            $(this).find('i').removeClass('fa-spin');
        }, 1000);
    });
    
    // Delete handler
    $(document).on('click', '.btn-delete', function() {
        const voucherId = $(this).data('id');
        const voucherCode = $(this).data('name');
        
        console.log('🗑️ Delete clicked:', voucherId, voucherCode);
        
        Swal.fire({
            title: 'Xác nhận xóa?',
            html: `Bạn có chắc chắn muốn xóa voucher<br><strong class="voucher-code">${voucherCode}</strong>?<br><small>Vouchers đã dùng vẫn giữ lại trong lịch sử đơn hàng.</small>`,
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
                deleteVoucher(voucherId);
            }
        });
    });
    
    // Toggle handler
    $(document).on('click', '.btn-toggle', function() {
        const voucherId = $(this).data('id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus ? 0 : 1;
        
        console.log('🔄 Toggle clicked:', voucherId, 'Current:', currentStatus, 'New:', newStatus);
        
        toggleVoucher(voucherId);
    });
});

function deleteVoucher(id) {
    console.log('🗑️ Deleting voucher:', id);
    
    Swal.fire({
        title: 'Đang xóa...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: ROUTES.delete.replace(':id', id),
        method: 'POST', // Sử dụng POST vì Blade @method('DELETE')
        data: {
            _method: 'DELETE',
            _token: CSRF_TOKEN
        },
        success: function(response) {
            console.log('✅ Deleted successfully:', response);
            
            Swal.fire({
                icon: 'success',
                title: 'Đã xóa!',
                text: response.message || 'Voucher đã được xóa thành công',
                timer: 2000,
                showConfirmButton: false
            });
            
            loadVouchers();
        },
        error: function(xhr) {
            console.error('❌ Delete failed:', xhr);
            
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: xhr.responseJSON?.message || 'Không thể xóa voucher',
            });
        }
    });
}

function toggleVoucher(id) {
    console.log('🔄 Toggling voucher:', id);
    
    $.ajax({
        url: ROUTES.toggle.replace(':id', id),
        method: 'POST', // Sử dụng POST vì Blade @method('PUT')
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
            
            loadVouchers();
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