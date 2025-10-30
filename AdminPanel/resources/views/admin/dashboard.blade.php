@extends('adminlte::page')

@section('title', 'Dashboard Quản trị')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </h1>
        </div>
        <div class="col-sm-6">
            <div class="float-right">
                <button type="button" class="btn btn-sm btn-primary" id="btn-refresh-dashboard">
                    <i class="fas fa-sync-alt"></i> Làm mới
                </button>
                <span class="badge badge-info ml-2">
                    <i class="far fa-clock"></i> 
                    <span id="current-time"></span>
                </span>
            </div>
        </div>
    </div>
@stop

@section('content')
    {{-- Welcome Message --}}
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h5><i class="fas fa-user-tie"></i> Chào mừng trở lại!</h5>
        <strong>{{ Auth::guard('admin')->user()->fullName ?? 'Người dùng' }}</strong>
        <br>
        <small>
            Vai trò: <span class="badge badge-primary">{{ ucfirst(Auth::guard('admin')->user()->role ?? 'Không xác định') }}</span>
            | Đăng nhập lần cuối: <strong>{{ Auth::guard('admin')->user()->updated_at ? Auth::guard('admin')->user()->updated_at->diffForHumans() : 'N/A' }}</strong>
        </small>
    </div>

    {{-- ROW 1: MAIN STATISTICS --}}
    <div class="row">
        {{-- Revenue (Admin/Marketing) --}}
        @if (Gate::allows('admin') || Gate::allows('marketing'))
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ number_format($totalRevenue ?? 0, 0, ',', '.') }}đ</h3>
                    <p>Doanh Thu Đã Thu</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <a href="#" class="small-box-footer">
                    Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        @endif

        {{-- Pending Orders (Admin/Staff) --}}
        @if (Gate::allows('admin') || Gate::allows('staff'))
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $newOrders ?? 0 }}</h3>
                    <p>Đơn Hàng Chờ Xử Lý</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="small-box-footer">
                    Xử lý ngay <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        @endif

        {{-- Low Stock (Admin/Staff) --}}
        @if (Gate::allows('admin') || Gate::allows('staff'))
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $lowStockProducts ?? 0 }}</h3>
                    <p>Sản Phẩm Sắp Hết</p>
                </div>
                <div class="icon">
                    <i class="fas fa-warehouse"></i>
                </div>
                <a href="{{ route('admin.products.index') }}" class="small-box-footer">
                    Xem kho <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        @endif

        {{-- Pending Reviews (Admin/Staff) --}}
        @if (Gate::allows('admin') || Gate::allows('staff'))
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $pendingReviews ?? 0 }}</h3>
                    <p>Đánh Giá Chờ Duyệt</p>
                </div>
                <div class="icon">
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <a href="{{ route('admin.reviews.index') }}" class="small-box-footer">
                    Duyệt ngay <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- ROW 2: ADDITIONAL STATISTICS --}}
    <div class="row">
        {{-- Total Products --}}
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-boxes"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Tổng Sản Phẩm</span>
                    <span class="info-box-number">{{ $totalProducts ?? 0 }}</span>
                    <small class="text-muted">{{ $activeProducts ?? 0 }} đang bán</small>
                </div>
            </div>
        </div>

        {{-- Total Orders This Month --}}
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Đơn Hàng Tháng Này</span>
                    <span class="info-box-number">{{ $ordersThisMonth ?? 0 }}</span>
                    <small class="text-muted">
                        @if(($ordersGrowth ?? 0) > 0)
                            <i class="fas fa-arrow-up text-success"></i> +{{ $ordersGrowth ?? 0 }}%
                        @else
                            <i class="fas fa-arrow-down text-danger"></i> {{ $ordersGrowth ?? 0 }}%
                        @endif
                    </small>
                </div>
            </div>
        </div>

        {{-- Total Customers --}}
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Tổng Khách Hàng</span>
                    <span class="info-box-number">{{ $totalCustomers ?? 0 }}</span>
                    <small class="text-muted">{{ $newCustomersThisMonth ?? 0 }} mới tháng này</small>
                </div>
            </div>
        </div>

        {{-- Average Order Value --}}
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-money-bill-wave"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Giá Trị Đơn TB</span>
                    <span class="info-box-number">{{ number_format($avgOrderValue ?? 0, 0, ',', '.') }}đ</span>
                    <small class="text-muted">Trung bình</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 3: CHARTS --}}
    <div class="row">
        {{-- Chart 1: Top Selling Products --}}
        <div class="col-lg-6">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> Top 5 Sản phẩm Bán Chạy
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="topSellingChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Dựa trên số lượng đã bán trong 30 ngày qua
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart 2: Revenue by Status --}}
        <div class="col-lg-6">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie"></i> Doanh Thu Theo Trạng Thái
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="revenueStatusChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Phân bổ doanh thu theo trạng thái đơn hàng
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW 4: ADDITIONAL CHARTS --}}
    <div class="row">
        {{-- Chart 3: Revenue Trend (Last 7 days) --}}
        <div class="col-lg-8">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-line"></i> Xu Hướng Doanh Thu (7 ngày qua)
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activities --}}
        <div class="col-lg-4">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i> Hoạt Động Gần Đây
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($recentActivities ?? [] as $activity)
                        <li class="list-group-item">
                            <i class="fas fa-{{ $activity['icon'] ?? 'circle' }} text-{{ $activity['color'] ?? 'primary' }}"></i>
                            <strong>{{ $activity['title'] }}</strong>
                            <br>
                            <small class="text-muted">{{ $activity['time'] }}</small>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted">
                            <i class="fas fa-inbox"></i>
                            <p class="mb-0">Chưa có hoạt động nào</p>
                        </li>
                        @endforelse
                    </ul>
                </div>
                @if(count($recentActivities ?? []) > 0)
                <div class="card-footer text-center">
                    <a href="#" class="text-primary">Xem tất cả</a>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ROW 5: QUICK ACTIONS --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt"></i> Thao Tác Nhanh
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @can('staff')
                        <div class="col-md-3 col-6">
                            <a href="{{ route('admin.products.create') }}" class="btn btn-app bg-success">
                                <i class="fas fa-plus"></i> Thêm Sản Phẩm
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-app bg-info">
                                <span class="badge bg-danger">{{ $newOrders ?? 0 }}</span>
                                <i class="fas fa-shopping-cart"></i> Đơn Hàng
                            </a>
                        </div>
                        @endcan
                        
                        @can('marketing')
                        <div class="col-md-3 col-6">
                            <a href="{{ route('admin.vouchers.create') }}" class="btn btn-app bg-warning">
                                <i class="fas fa-tags"></i> Tạo Voucher
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="{{ route('admin.product-discounts.create') }}" class="btn btn-app bg-danger">
                                <i class="fas fa-percentage"></i> Tạo Sale
                            </a>
                        </div>
                        @endcan
                        
                        @can('admin')
                        <div class="col-md-3 col-6">
                            <a href="{{ route('admin.employees.index') }}" class="btn btn-app bg-primary">
                                <i class="fas fa-users-cog"></i> Quản Lý NV
                            </a>
                        </div>
                        <div class="col-md-3 col-6">
                            <a href="{{ route('admin.categories.index') }}" class="btn btn-app bg-secondary">
                                <i class="fas fa-folder"></i> Danh Mục
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .small-box h3 {
        font-size: 2rem;
    }
    
    .info-box-number {
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .chart-container {
        position: relative;
        margin: auto;
    }
    
    .btn-app {
        width: 100%;
        margin: 5px;
    }
    
    .card-outline {
        border-top: 3px solid;
    }
    
    .list-group-item {
        border-left: none;
        border-right: none;
    }
    
    .list-group-item:first-child {
        border-top: none;
    }
</style>
@stop

@section('js')
{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ============================================================================
// DASHBOARD JAVASCRIPT
// ============================================================================
$(document).ready(function() {
    console.log('✅ Dashboard loaded');
    
    // Update current time
    function updateTime() {
        const now = new Date();
        const timeString = now.toLocaleString('vi-VN', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        $('#current-time').text(timeString);
    }
    
    updateTime();
    setInterval(updateTime, 1000);
    
    // Refresh button
    $('#btn-refresh-dashboard').on('click', function() {
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        
        setTimeout(function() {
            location.reload();
        }, 500);
    });
    
    // ========================================================================
    // CHART DATA FROM PHP
    // ========================================================================
    const topLabels = @json($chart_top_labels ?? []);
    const topData = @json($chart_top_data ?? []);
    const statusLabels = @json($chart_status_labels ?? []);
    const statusData = @json($chart_status_data ?? []);
    const revenueDates = @json($chart_revenue_dates ?? []);
    const revenueData = @json($chart_revenue_data ?? []);
    
    console.log('📊 Chart data loaded:', {
        topLabels,
        topData,
        statusLabels,
        statusData,
        revenueDates,
        revenueData
    });
    
    // ========================================================================
    // CHART 1: TOP SELLING PRODUCTS (HORIZONTAL BAR)
    // ========================================================================
    const topSellingCtx = document.getElementById('topSellingChart').getContext('2d');
    new Chart(topSellingCtx, {
        type: 'bar',
        data: {
            labels: topLabels,
            datasets: [{
                label: 'Số lượng bán',
                data: topData,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(153, 102, 255, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            indexAxis: 'y', // This makes it horizontal
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // ========================================================================
    // CHART 2: REVENUE BY STATUS (DOUGHNUT)
    // ========================================================================
    const revenueStatusCtx = document.getElementById('revenueStatusChart').getContext('2d');
    new Chart(revenueStatusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: [
                    '#3c8dbc', // Pending
                    '#00a65a', // Completed
                    '#dd4b39', // Cancelled
                    '#f39c12', // Processing
                    '#d2d6de'  // Other
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 15,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return label + ': ' + value.toLocaleString('vi-VN') + 'đ (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // ========================================================================
    // CHART 3: REVENUE TREND (LINE)
    // ========================================================================
    const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
    new Chart(revenueTrendCtx, {
        type: 'line',
        data: {
            labels: revenueDates,
            datasets: [{
                label: 'Doanh thu',
                data: revenueData,
                fill: true,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Doanh thu: ' + context.parsed.y.toLocaleString('vi-VN') + 'đ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + 'đ';
                        }
                    }
                }
            }
        }
    });
    
    console.log('✅ All charts rendered successfully');
});
</script>
@endsection