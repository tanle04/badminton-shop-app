@extends('adminlte::page')

@section('title', 'Dashboard Quản trị')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <p>Chào mừng, {{ Auth::guard('admin')->user()->fullName ?? 'Người dùng' }} đến với khu vực quản trị của cửa hàng cầu lông!</p>
    <p>Vai trò của bạn: <b>{{ Auth::guard('admin')->user()->role ?? 'Không xác định' }}</b></p>
    
    <hr>

    {{-- HÀNG 1: CÁC WIDGET THỐNG KÊ CHÍNH --}}
    <div class="row">
        
        {{-- Widget 1: DOANH THU TỔNG (Admin/Marketing) --}}
        @if (Gate::allows('admin') || Gate::allows('marketing'))
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Tổng Doanh Thu Đã Thanh Toán</span>
                    <span class="info-box-number">{{ number_format($totalRevenue ?? 0, 0, ',', '.') }} VNĐ</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Widget 2: ĐƠN HÀNG MỚI (Admin/Staff) --}}
        @if (Gate::allows('admin') || Gate::allows('staff'))
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Đơn Hàng Chờ Xử Lý (Pending)</span>
                    <span class="info-box-number">{{ $newOrders ?? 0 }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Widget 3: SẢN PHẨM CẦN NHẬP KHO (Admin/Staff) --}}
        @if (Gate::allows('admin') || Gate::allows('staff'))
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-warehouse"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Sản Phẩm Cần Nhập Kho (Tồn kho < 5)</span>
                    <span class="info-box-number">{{ $lowStockProducts ?? 0 }}</span>
                </div>
            </div>
        </div>
        @endif

        {{-- Widget 4: ĐÁNH GIÁ CẦN DUYỆT (Admin/Staff) --}}
        @if (Gate::allows('admin') || Gate::allows('staff'))
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-star-half-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Đánh Giá Chờ Duyệt</span>
                    <span class="info-box-number">{{ $pendingReviews ?? 0 }}</span>
                </div>
            </div>
        </div>
        @endif
        
    </div>

    <hr>
    
    {{-- HÀNG 2: BIỂU ĐỒ ĐỘNG (Sử dụng Canvas) --}}
    <div class="row">
        
        {{-- Biểu đồ 1: TOP SẢN PHẨM BÁN CHẠY --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Top 5 Sản phẩm Bán Chạy Nhất (Số lượng)</h3>
                </div>
                <div class="card-body">
                    <canvas id="topSellingChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
        {{-- Biểu đồ 2: DOANH THU THEO TRẠNG THÁI --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Tổng Doanh Thu Phân loại theo Trạng thái ĐH</h3>
                </div>
                <div class="card-body">
                    <canvas id="revenueStatusChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        
    </div>
    
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script> {{-- Tải thư viện Chart.js --}}
<script>
    $(document).ready(function() {
        
        // Dữ liệu từ PHP được truyền qua biến $data
        const topLabels = @json($chart_top_labels ?? []);
        const topData = @json($chart_top_data ?? []);
        const statusLabels = @json($chart_status_labels ?? []);
        const statusData = @json($chart_status_data ?? []);

        // --- 1. BIỂU ĐỒ TOP SẢN PHẨM BÁN CHẠY (BAR CHART) ---
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
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(54, 162, 235, 0.8)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // --- 2. BIỂU ĐỒ DOANH THU THEO TRẠNG THÁI (PIE CHART) ---
        const revenueStatusCtx = document.getElementById('revenueStatusChart').getContext('2d');
        new Chart(revenueStatusCtx, {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Tổng Doanh thu (VNĐ)',
                    data: statusData,
                    backgroundColor: [
                        '#3c8dbc', // info 
                        '#00a65a', // success 
                        '#dd4b39', // danger 
                        '#f39c12', // warning 
                        '#d2d6de'  // lightgray
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: false }
                }
            }
        });
    });
</script>
@endsection