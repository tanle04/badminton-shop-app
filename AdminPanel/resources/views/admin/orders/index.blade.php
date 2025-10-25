@extends('adminlte::page')

@section('title', 'Quản lý Đơn hàng')

@section('content_header')
    <h1>Danh sách Đơn hàng</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tất cả Đơn hàng</h3>
            {{-- Thêm bộ lọc nhanh nếu cần --}}
        </div>
        <div class="card-body p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Khách hàng</th>
                        <th>Ngày đặt</th>
                        <th>Tổng tiền</th>
                        <th>PT Thanh toán</th>
                        <th>Trạng thái ĐH</th>
                        <th>Trạng thái TT</th>
                        <th style="width: 100px">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->orderID }}</td>
                        <td>{{ $order->customer->fullName ?? 'Khách lẻ' }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->orderDate)->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($order->total, 0, ',', '.') }} VNĐ</td>
                        <td>{{ $order->paymentMethod }}</td>
                        <td>
                            <span class="badge 
                                @if($order->status == 'Delivered') badge-success 
                                @elseif($order->status == 'Processing' || $order->status == 'Shipped') badge-info 
                                @elseif($order->status == 'Cancelled' || $order->status == 'Refunded') badge-danger 
                                @else badge-secondary @endif">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>
                            <span class="badge @if($order->paymentStatus == 'Paid') badge-success @elseif($order->paymentStatus == 'Unpaid') badge-warning @else badge-danger @endif">
                                {{ $order->paymentStatus }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-primary btn-xs">Chi tiết</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $orders->links('pagination::bootstrap-4') }}
        </div>
    </div>
@stop