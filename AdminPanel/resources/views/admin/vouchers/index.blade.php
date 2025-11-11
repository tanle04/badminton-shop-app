@extends('adminlte::page')

@section('title', 'Quản lý Mã giảm giá')

@section('content_header')
    <div class="row align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">
                <i class="fas fa-ticket-alt text-primary"></i> Quản lý Mã giảm giá
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Vouchers</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    {{-- CSRF Token cho AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeInDown">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-gradient-info shadow-sm hover-lift">
                <div class="inner">
                    <h3 id="stat-total" class="counter">-</h3>
                    <p>Tổng số Vouchers</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="small-box-footer py-2">
                    <span class="text-white-50"><small>Tất cả mã giảm giá</small></span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-gradient-success shadow-sm hover-lift">
                <div class="inner">
                    <h3 id="stat-active" class="counter">-</h3>
                    <p>Đang hoạt động</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="small-box-footer py-2">
                    <span class="text-white-50"><small>Vouchers khả dụng</small></span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-gradient-warning shadow-sm hover-lift">
                <div class="inner">
                    <h3 id="stat-expired" class="counter">-</h3>
                    <p>Đã hết hạn</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="small-box-footer py-2">
                    <span class="text-white-50"><small>Cần gia hạn hoặc xóa</small></span>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="small-box bg-gradient-danger shadow-sm hover-lift">
                <div class="inner">
                    <h3 id="stat-inactive" class="counter">-</h3>
                    <p>Tạm ngưng</p>
                </div>
                <div class="icon">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="small-box-footer py-2">
                    <span class="text-white-50"><small>Đã vô hiệu hóa</small></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card card-primary card-outline shadow">
        {{-- Card Header với Search & Filters --}}
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
                               placeholder="Tìm kiếm mã, tên voucher...">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary border-left-0" type="button" id="btn-clear-search" title="Xóa tìm kiếm">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7 text-md-right">
                    <div class="btn-toolbar justify-content-md-end" role="toolbar">
                        <div class="btn-group mr-2" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-filter" title="Bộ lọc">
                                <i class="fas fa-filter"></i> <span class="d-none d-md-inline">Lọc</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh" title="Làm mới">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        
                        <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Tạo Voucher
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Panel (Ẩn mặc định) --}}
        <div class="card-body border-bottom bg-light" id="filter-panel" style="display: none;">
            <form id="filter-form">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold">Trạng thái</label>
                        <select class="form-control form-control-sm" name="status">
                            <option value="">Tất cả</option>
                            <option value="active">✅ Đang hoạt động</option>
                            <option value="inactive">⛔ Tạm ngưng</option>
                            <option value="expired">⏰ Hết hạn</option>
                            <option value="upcoming">📅 Sắp diễn ra</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold">Loại giảm giá</label>
                        <select class="form-control form-control-sm" name="type">
                            <option value="">Tất cả</option>
                            <option value="percentage">% Phần trăm</option>
                            <option value="fixed">💵 Cố định (VNĐ)</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold">Phạm vi</label>
                        <select class="form-control form-control-sm" name="scope">
                            <option value="">Tất cả</option>
                            <option value="public">🌐 Công khai</option>
                            <option value="private">🔒 Riêng tư</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label class="small font-weight-bold">Hiển thị</label>
                        <select class="form-control form-control-sm" id="per-page-select">
                            <option value="10">10 mục</option>
                            <option value="15" selected>15 mục</option>
                            <option value="25">25 mục</option>
                            <option value="50">50 mục</option>
                            <option value="100">100 mục</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-filter">
                            <i class="fas fa-redo"></i> Đặt lại
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-search"></i> Áp dụng bộ lọc
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

            <div class="table-responsive">
                <table id="vouchersTable" class="table table-hover table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 15%" class="sortable" data-sort="voucherCode">
                                Mã Code <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 18%" class="sortable" data-sort="voucherName">
                                Tên Voucher <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 17%">Giá trị / Loại</th>
                            <th style="width: 12%" class="text-center sortable" data-sort="usedCount">
                                Sử dụng <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 15%" class="sortable" data-sort="startDate">
                                Thời gian <i class="fas fa-sort text-muted"></i>
                            </th>
                            <th style="width: 10%" class="text-center">Trạng thái</th>
                            <th style="width: 13%" class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="loading-row">
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Đang tải...</span>
                                </div>
                                <p class="mt-3 text-muted">Đang tải dữ liệu...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer bg-white">
            <div class="row align-items-center">
                <div class="col-sm-5">
                    <div id="pagination-info" class="text-muted">
                        <small><i class="fas fa-info-circle"></i> Đang tải...</small>
                    </div>
                </div>
                <div class="col-sm-7">
                    <nav id="pagination-container" class="float-sm-right">
                        {{-- Pagination sẽ được render bởi JS --}}
                    </nav>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
{{-- Animate.css --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    /* ============ GENERAL ANIMATIONS ============ */
    .hover-lift {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.15) !important;
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { 
            opacity: 0; 
            transform: translateY(20px); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0); 
        }
    }

    /* ============ SMALL BOX ============ */
    .small-box {
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }
    
    .small-box h3 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .small-box .icon {
        font-size: 80px;
        opacity: 0.25;
        transition: all 0.3s ease;
    }
    
    .small-box:hover .icon {
        opacity: 0.4;
        transform: scale(1.1);
    }
    
    .small-box-footer {
        background-color: rgba(0,0,0,0.1);
    }

    /* ============ TABLE STYLES ============ */
    .table {
        margin-bottom: 0;
    }
    
    .table-hover tbody tr {
        transition: all 0.2s ease;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
        transform: scale(1.005);
        box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    }
    
    .table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .sortable {
        cursor: pointer;
        user-select: none;
        transition: all 0.2s ease;
    }
    
    .sortable:hover {
        background-color: #e9ecef !important;
    }
    
    .sortable.active {
        background-color: #007bff !important;
        color: white !important;
    }
    
    .sortable.active i {
        color: white !important;
    }
    
    .sortable i {
        font-size: 0.75rem;
        margin-left: 0.25rem;
        transition: transform 0.2s ease;
    }
    
    .sortable.asc i {
        transform: rotate(180deg);
    }

    /* ============ VOUCHER CODE ============ */
    .voucher-code {
        font-family: 'Courier New', Consolas, monospace;
        font-weight: 700;
        color: #d90f23;
        font-size: 1.05rem;
        background: linear-gradient(135deg, #fff5f5 0%, #fdf0f1 100%);
        padding: 0.35em 0.7em;
        border-radius: 6px;
        border: 2px dashed #f5c6cb;
        display: inline-block;
        box-shadow: 0 2px 5px rgba(217, 15, 35, 0.1);
        transition: all 0.3s ease;
    }
    
    .voucher-code:hover {
        transform: scale(1.05) rotate(-1deg);
        box-shadow: 0 4px 10px rgba(217, 15, 35, 0.2);
        border-color: #d90f23;
    }
    
    .voucher-name {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.25rem;
    }
    
    .voucher-meta {
        font-size: 0.8rem;
        color: #6c757d;
    }

    /* ============ DISCOUNT VALUE ============ */
    .discount-value {
        font-weight: 700;
        color: #007bff;
        font-size: 1.15rem;
        line-height: 1.4;
    }
    
    .discount-icon {
        font-size: 0.9rem;
        margin-right: 0.25rem;
    }
    
    .discount-max, .min-order {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.25rem;
        line-height: 1.3;
    }
    
    .min-order {
        color: #28a745;
    }

    /* ============ USAGE DISPLAY ============ */
    .usage-container {
        padding: 0.5rem 0;
    }
    
    .usage-text {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.4rem;
    }
    
    .usage-progress {
        height: 10px;
        border-radius: 10px;
        background-color: #e9ecef;
        overflow: hidden;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
    }
    
    .usage-progress-bar {
        height: 100%;
        transition: width 0.6s ease, background-color 0.3s ease;
        border-radius: 10px;
        background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    }
    
    .usage-progress-bar.warning {
        background: linear-gradient(90deg, #ffc107 0%, #ff9800 100%);
    }
    
    .usage-progress-bar.danger {
        background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
    }

    /* ============ DATE RANGE ============ */
    .date-range {
        font-size: 0.85rem;
        line-height: 1.8;
    }
    
    .date-range i {
        width: 18px;
        text-align: center;
    }

    /* ============ STATUS BADGES ============ */
    .badge-status {
        font-size: 0.8rem;
        padding: 0.45em 0.8em;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .badge-status i {
        margin-right: 0.3rem;
    }

    /* ============ BUTTONS ============ */
    .btn-xs {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        line-height: 1.3;
        border-radius: 0.2rem;
    }
    
    .btn-action {
        transition: all 0.2s ease;
        margin: 0 2px;
    }
    
    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .btn-group-sm > .btn, .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* ============ SEARCH INPUT ============ */
    #search-input {
        border-left: none !important;
        border-right: none !important;
    }
    
    #search-input:focus {
        box-shadow: none;
        border-color: #80bdff;
    }
    
    .input-group-text {
        border-color: #ced4da;
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
    
    /* ============ PAGINATION ============ */
    .pagination {
        margin-bottom: 0;
    }
    
    .page-link {
        color: #007bff;
        transition: all 0.2s ease;
    }
    
    .page-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .page-item.active .page-link {
        background-color: #007bff;
        border-color: #007bff;
        box-shadow: 0 3px 8px rgba(0,123,255,0.3);
    }

    /* ============ FILTER PANEL ============ */
    #filter-panel {
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            max-height: 0;
        }
        to {
            opacity: 1;
            max-height: 500px;
        }
    }
    
    /* ============ EMPTY STATE ============ */
    .empty-state {
        padding: 3rem 1rem;
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
        
        .small-box .icon {
            font-size: 60px;
        }
        
        .voucher-code {
            font-size: 0.9rem;
        }
        
        .btn-group {
            display: flex;
            flex-direction: column;
        }
        
        .btn-group > .btn {
            margin-bottom: 0.25rem;
        }
    }
</style>
@stop

@section('js')
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ============================================================================
// CONSTANTS & GLOBAL VARIABLES
// ============================================================================
const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');

// ⭐️⭐️⭐️ BƯỚC SỬA LỖI ⭐️⭐️⭐️
// Thay thế các URL cứng bằng hàm route() của Laravel
const ROUTES = {
    apiIndex: '{{ route("admin.vouchers.apiIndex") }}',
    apiStats: '{{ route("admin.vouchers.apiStats") }}',
    edit: '{{ route("admin.vouchers.edit", ["voucher" => ":id"]) }}',         // <-- ĐÃ SỬA
    delete: '{{ route("admin.vouchers.destroy", ["voucher" => ":id"]) }}',   // <-- ĐÃ SỬA
    toggle: '{{ route("admin.vouchers.toggleActive", ["voucher" => ":id"]) }}' // <-- ĐÃ SỬA
};
// ⭐️⭐️⭐️ KẾT THÚC BƯỚC SỬA LỖI ⭐️⭐️⭐️

let currentPage = 1;
let currentSort = { by: 'created_at', dir: 'desc' };
let searchTimeout;

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
const getStatusBadge = (voucher) => {
    // Lấy thời gian hiện tại theo múi giờ Việt Nam
    const now = new Date();
    const endDate = new Date(voucher.endDate);
    const startDate = new Date(voucher.startDate);
    
    if (!voucher.isActive) {
        return '<span class="badge badge-danger badge-status"><i class="fas fa-ban"></i> Tạm ngưng</span>';
    } else if (endDate < now) {
        return '<span class="badge badge-warning badge-status"><i class="fas fa-clock"></i> Hết hạn</span>';
    } else if (startDate > now) {
        return '<span class="badge badge-info badge-status"><i class="fas fa-calendar-alt"></i> Sắp diễn ra</span>';
    } else {
        return '<span class="badge badge-success badge-status"><i class="fas fa-check-circle"></i> Hoạt động</span>';
    }
};

const getValueDisplay = (voucher) => {
    let html = '<div class="discount-value">';
    
    if (voucher.discountType === 'percentage') {
        html += `<i class="fas fa-percent discount-icon"></i>${voucher.discountValue}%`;
        if (voucher.maxDiscountAmount) {
            html += `<div class="discount-max">Tối đa: ${formatCurrency(voucher.maxDiscountAmount)}</div>`;
        }
    } else {
        html += `<i class="fas fa-dollar-sign discount-icon"></i>${formatCurrency(voucher.discountValue)}`;
    }
    
    html += `</div>`;
    html += `<div class="min-order"><i class="fas fa-shopping-cart"></i> Đơn tối thiểu: ${formatCurrency(voucher.minOrderValue)}</div>`;
    return html;
};

const getUsageDisplay = (voucher) => {
    const percentage = (voucher.usedCount / voucher.maxUsage) * 100;
    let barClass = '';
    
    if (percentage >= 80) barClass = 'danger';
    else if (percentage >= 50) barClass = 'warning';
    
    return `
        <div class="usage-container">
            <div class="usage-text">
                <strong>${voucher.usedCount}</strong> / ${voucher.maxUsage}
                <span class="text-muted">(${percentage.toFixed(0)}%)</span>
            </div>
            <div class="usage-progress">
                <div class="usage-progress-bar ${barClass}" style="width: ${percentage}%"></div>
            </div>
        </div>
    `;
};

// ============================================================================
// LOAD STATISTICS
// ============================================================================
function loadStats() {
    $.ajax({
        url: ROUTES.apiStats,
        method: 'GET',
        success: function(stats) {
            console.log('📊 Stats loaded:', stats);
            
            // Animate numbers
            animateValue('stat-total', 0, stats.total, 1000);
            animateValue('stat-active', 0, stats.active, 1000);
            animateValue('stat-expired', 0, stats.expired, 1000);
            animateValue('stat-inactive', 0, stats.inactive, 1000);
        },
        error: function(xhr) {
            console.error('❌ Error loading stats:', xhr);
        }
    });
}

function animateValue(id, start, end, duration) {
    const element = document.getElementById(id);
    const range = end - start;
    const increment = end > start ? 1 : -1;
    const stepTime = Math.abs(Math.floor(duration / range));
    let current = start;
    
    const timer = setInterval(function() {
        current += increment;
        element.textContent = current;
        if (current == end) {
            clearInterval(timer);
        }
    }, stepTime);
}

// ============================================================================
// RENDER VOUCHERS
// ============================================================================
function renderVouchers(data) {
    console.log('📊 Rendering', data.length, 'vouchers');
    
    if (data.length === 0) {
        $('#vouchersTable tbody').html(`
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p class="text-muted mb-3">Không tìm thấy mã giảm giá nào</p>
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
        // ⭐️ SỬA LỖI: Dùng replace() trên hằng số ROUTES đã được sửa
        const editUrl = ROUTES.edit.replace(':id', voucher.voucherID);
        
        rows += `
        <tr class="fade-in" data-id="${voucher.voucherID}">
            <td>
                <span class="voucher-code">${voucher.voucherCode}</span>
            </td>
            <td>
                <div class="voucher-name">${voucher.voucherName || voucher.voucherCode}</div>
                ${voucher.description ? `<div class="voucher-meta"><i class="fas fa-info-circle"></i> ${voucher.description}</div>` : ''}
                <div class="voucher-meta"><i class="fas fa-calendar"></i> ${formatDate(voucher.created_at)}</div>
            </td>
            <td>${getValueDisplay(voucher)}</td>
            <td class="text-center">${getUsageDisplay(voucher)}</td>
            <td class="date-range">
                <div><i class="fas fa-calendar-alt text-success"></i> ${formatDate(voucher.startDate)}</div>
                <div><i class="fas fa-calendar-times text-danger"></i> ${formatDate(voucher.endDate)}</div>
            </td>
            <td class="text-center">${getStatusBadge(voucher)}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="${editUrl}" 
                       class="btn btn-warning btn-xs btn-action" 
                       title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </a>
                    
                    <button class="btn ${voucher.isActive ? 'btn-secondary' : 'btn-success'} btn-xs btn-toggle btn-action" 
                            data-id="${voucher.voucherID}" 
                            data-status="${voucher.isActive ? 1 : 0}"
                            title="${voucher.isActive ? 'Tắt' : 'Bật'}">
                        <i class="fas ${voucher.isActive ? 'fa-toggle-off' : 'fa-toggle-on'}"></i>
                    </button>
                    
                    <button class="btn btn-danger btn-xs btn-delete btn-action" 
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
}

// ============================================================================
// RENDER PAGINATION
// ============================================================================
function renderPagination(pagination) {
    const { current_page, last_page, from, to, total } = pagination;
    
    // Info text
    $('#pagination-info').html(`
        <small class="text-muted">
            <i class="fas fa-info-circle"></i> 
            Hiển thị <strong>${from}</strong> - <strong>${to}</strong> 
            trong tổng số <strong>${total}</strong> vouchers
        </small>
    `);
    
    // Pagination links
    if (last_page <= 1) {
        $('#pagination-container').html('');
        return;
    }
    
    let html = '<ul class="pagination pagination-sm mb-0">';
    
    // Previous
    html += `
        <li class="page-item ${current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${current_page - 1}">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;
    
    // Page numbers
    let startPage = Math.max(1, current_page - 2);
    let endPage = Math.min(last_page, current_page + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (startPage > 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <li class="page-item ${i === current_page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    if (endPage < last_page) {
        if (endPage < last_page - 1) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="#" data-page="${last_page}">${last_page}</a></li>`;
    }
    
    // Next
    html += `
        <li class="page-item ${current_page === last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${current_page + 1}">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    html += '</ul>';
    $('#pagination-container').html(html);
}

// ============================================================================
// LOAD VOUCHERS
// ============================================================================
function loadVouchers(page = 1) {
    console.log('🔄 Loading vouchers... Page:', page);
    
    $('#loading-overlay').show();
    currentPage = page;
    
    // Build query params
    let params = {
        page: page,
        per_page: $('#per-page-select').val() || 15,
        sort_by: currentSort.by,
        sort_dir: currentSort.dir
    };
    
    // Add search
    const searchValue = $('#search-input').val().trim();
    if (searchValue) {
        params.search = searchValue;
    }
    
    // Add filters
    if ($('#filter-panel').is(':visible')) {
        const filterData = $('#filter-form').serializeArray();
        filterData.forEach(item => {
            if (item.value) {
                params[item.name] = item.value;
            }
        });
    }
    
    console.log('📤 Request params:', params);
    
    $.ajax({
        url: ROUTES.apiIndex,
        method: 'GET',
        data: params,
        success: function(response) {
            console.log('✅ Data loaded:', response);
            
            renderVouchers(response.data);
            renderPagination(response);
            
            $('#loading-overlay').hide();
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading data:', error, xhr);
            
            $('#loading-overlay').hide();
            
            $('#vouchersTable tbody').html(`
                <tr>
                    <td colspan="7" class="text-center text-danger py-5">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p><strong>Lỗi tải dữ liệu!</strong></p>
                        <p class="text-muted">${xhr.responseJSON?.message || error}</p>
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
// DELETE VOUCHER
// ============================================================================
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
        // ⭐️ SỬA LỖI: Dùng replace() trên hằng số ROUTES đã được sửa
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
                text: response.message || 'Voucher đã được xóa thành công',
                timer: 2000,
                showConfirmButton: false
            });
            
            loadVouchers(currentPage);
            loadStats();
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

// ============================================================================
// TOGGLE VOUCHER
// ============================================================================
function toggleVoucher(id) {
    console.log('🔄 Toggling voucher:', id);
    
    $.ajax({
        // ⭐️ SỬA LỖI: Dùng replace() trên hằng số ROUTES đã được sửa
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
            
            loadVouchers(currentPage);
            loadStats();
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

// ============================================================================
// EVENT HANDLERS
// ============================================================================
$(document).ready(function() {
    console.log('✅ Document ready');
    console.log('⏰ Client timezone:', Intl.DateTimeFormat().resolvedOptions().timeZone);
    
    // Load initial data
    loadVouchers();
    loadStats();
    
    // === SEARCH ===
    $('#search-input').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadVouchers(1);
        }, 500); // Debounce 500ms
    });
    
    $('#btn-clear-search').on('click', function() {
        $('#search-input').val('');
        loadVouchers(1);
    });
    
    // === FILTER PANEL ===
    $('#btn-filter').on('click', function() {
        $('#filter-panel').slideToggle(300);
        $(this).toggleClass('active');
    });
    
    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        loadVouchers(1);
    });
    
    $('#btn-reset-filter').on('click', function() {
        $('#filter-form')[0].reset();
        $('#per-page-select').val(15);
        loadVouchers(1);
    });
    
    $('#per-page-select').on('change', function() {
        loadVouchers(1);
    });
    
    // === REFRESH ===
    $('#btn-refresh').on('click', function() {
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        
        loadVouchers(currentPage);
        loadStats();
        
        setTimeout(() => {
            $icon.removeClass('fa-spin');
        }, 1000);
    });
    
    // === SORTING ===
    $(document).on('click', '.sortable', function() {
        const sortBy = $(this).data('sort');
        
        // Toggle direction
        if (currentSort.by === sortBy) {
            currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
        } else {
            currentSort.by = sortBy;
            currentSort.dir = 'asc';
        }
        
        // Update UI
        $('.sortable').removeClass('active asc desc');
        $(this).addClass('active ' + currentSort.dir);
        
        loadVouchers(1);
    });
    
    // === PAGINATION ===
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            loadVouchers(page);
            
            // Scroll to top of table
            $('html, body').animate({
                scrollTop: $('#vouchersTable').offset().top - 100
            }, 300);
        }
    });
    
    // === DELETE ===
    $(document).on('click', '.btn-delete', function() {
        const voucherId = $(this).data('id');
        const voucherCode = $(this).data('name');
        
        console.log('🗑️ Delete clicked:', voucherId, voucherCode);
        
        Swal.fire({
            title: 'Xác nhận xóa?',
            html: `Bạn có chắc chắn muốn xóa voucher<br><strong class="voucher-code" style="font-size: 1.2rem;">${voucherCode}</strong>?<br><small class="text-muted">Vouchers đã sử dụng vẫn giữ lại trong lịch sử đơn hàng.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Xóa',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                deleteVoucher(voucherId);
            }
        });
    });
    
    // === TOGGLE ===
    $(document).on('click', '.btn-toggle', function() {
        const voucherId = $(this).data('id');
        toggleVoucher(voucherId);
    });
    
    // Auto-dismiss alerts
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
});
</script>
@stop
