@extends('adminlte::page')

@section('title', 'Quản lý Đơn hàng')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-shopping-cart"></i> Quản lý Đơn hàng
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Đơn hàng</li>
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
        @php
            $totalOrders = $orders->total();
            $pendingOrders = $orders->where('status', 'Pending')->count();
            $processingOrders = $orders->where('status', 'Processing')->count();
            $deliveredOrders = $orders->where('status', 'Delivered')->count();
        @endphp
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalOrders }}</h3>
                    <p>Tổng đơn hàng</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $pendingOrders }}</h3>
                    <p>Chờ xử lý</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $processingOrders }}</h3>
                    <p>Đang xử lý</p>
                </div>
                <div class="icon">
                    <i class="fas fa-spinner"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $deliveredOrders }}</h3>
                    <p>Đã giao</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> Danh sách Đơn hàng
            </h3>
            <div class="card-tools">
                {{-- Filter Buttons --}}
                <div class="btn-group btn-group-sm mr-2">
                    <a href="{{ route('admin.orders.index', ['status' => 'all']) }}" 
                       class="btn {{ request('status') == 'all' || !request('status') ? 'btn-secondary' : 'btn-default' }}">
                        <i class="fas fa-list"></i> Tất cả
                    </a>
                    <a href="{{ route('admin.orders.index', ['status' => 'Pending']) }}" 
                       class="btn {{ request('status') == 'Pending' ? 'btn-warning' : 'btn-default' }}">
                        <i class="fas fa-clock"></i> Pending
                    </a>
                    <a href="{{ route('admin.orders.index', ['status' => 'Processing']) }}" 
                       class="btn {{ request('status') == 'Processing' ? 'btn-info' : 'btn-default' }}">
                        <i class="fas fa-spinner"></i> Processing
                    </a>
                    <a href="{{ route('admin.orders.index', ['status' => 'Shipped']) }}" 
                       class="btn {{ request('status') == 'Shipped' ? 'btn-primary' : 'btn-default' }}">
                        <i class="fas fa-shipping-fast"></i> Shipped
                    </a>
                    <a href="{{ route('admin.orders.index', ['status' => 'Delivered']) }}" 
                       class="btn {{ request('status') == 'Delivered' ? 'btn-success' : 'btn-default' }}">
                        <i class="fas fa-check-circle"></i> Delivered
                    </a>
                </div>
                
                <button type="button" class="btn btn-tool" id="btn-refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 80px" class="text-center">ID</th>
                            <th style="width: 15%">Khách hàng</th>
                            <th style="width: 12%">Ngày đặt</th>
                            <th style="width: 12%" class="text-right">Tổng tiền</th>
                            <th style="width: 10%">PT Thanh toán</th>
                            <th style="width: 10%" class="text-center">Trạng thái ĐH</th>
                            <th style="width: 10%" class="text-center">Trạng thái TT</th>
                            <th style="width: 120px" class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                        <tr>
                            <td class="text-center">
                                <strong class="text-primary">#{{ $order->orderID }}</strong>
                            </td>
                            <td>
                                <div>
                                    <i class="fas fa-user"></i>
                                    <strong>{{ $order->customer->fullName ?? 'Khách lẻ' }}</strong>
                                </div>
                                @if($order->customer && $order->customer->email)
                                    <small class="text-muted">
                                        <i class="fas fa-envelope"></i> {{ $order->customer->email }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <i class="far fa-calendar-alt"></i>
                                    {{ \Carbon\Carbon::parse($order->orderDate)->format('d/m/Y') }}
                                </div>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($order->orderDate)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-right">
                                <strong class="text-success">
                                    {{ number_format($order->total, 0, ',', '.') }}đ
                                </strong>
                            </td>
                            <td>
                                @php
                                    $paymentIcons = [
                                        'COD' => 'fas fa-money-bill-wave',
                                        'VNPay' => 'fas fa-credit-card',
                                        'Momo' => 'fas fa-mobile-alt',
                                        'Banking' => 'fas fa-university'
                                    ];
                                    $icon = $paymentIcons[$order->paymentMethod] ?? 'fas fa-question-circle';
                                @endphp
                                <i class="{{ $icon }}"></i>
                                {{ $order->paymentMethod }}
                            </td>
                            <td class="text-center">
                                @php
                                    $statusColors = [
                                        'Pending' => 'warning',
                                        'Processing' => 'info',
                                        'Shipped' => 'primary',
                                        'Delivered' => 'success',
                                        'Cancelled' => 'danger',
                                        'Refunded' => 'secondary'
                                    ];
                                    $color = $statusColors[$order->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $color }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php
                                    $paymentColors = [
                                        'Paid' => 'success',
                                        'Unpaid' => 'warning',
                                        'Refunded' => 'danger'
                                    ];
                                    $pColor = $paymentColors[$order->paymentStatus] ?? 'secondary';
                                @endphp
                                <span class="badge badge-{{ $pColor }}">
                                    {{ $order->paymentStatus }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.orders.show', $order) }}" 
                                   class="btn btn-info btn-sm"
                                   title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Không có đơn hàng nào</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($orders->hasPages())
        <div class="card-footer clearfix">
            <div class="float-left">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Hiển thị {{ $orders->firstItem() }} - {{ $orders->lastItem() }} 
                    trong tổng số <strong>{{ $orders->total() }}</strong> đơn hàng
                </small>
            </div>
            <div class="float-right">
                {{ $orders->appends(['status' => request('status')])->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
@stop

@section('css')
<style>
    .small-box h3 {
        font-size: 2rem;
    }
    
    .card-outline {
        border-top: 3px solid #007bff;
    }
    
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
    }
    
    .badge {
        font-size: 0.85em;
        padding: 0.4em 0.6em;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    console.log('✅ Orders index loaded');
    
    // Refresh button
    $('#btn-refresh').on('click', function() {
        const $icon = $(this).find('i');
        $icon.addClass('fa-spin');
        setTimeout(function() {
            location.reload();
        }, 500);
    });
    
    // Auto dismiss alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@stop