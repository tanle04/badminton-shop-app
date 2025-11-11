@extends('adminlte::page')

@section('title', 'Chi tiết Khách hàng')

@section('content_header')
    <h1 class="m-0 text-dark">
        <i class="fas fa-user-circle"></i> Chi tiết Khách hàng: {{ $customer->fullName }}
    </h1>
@stop

@section('content')
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        {{-- Thông tin cá nhân --}}
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <div class="profile-user-img img-fluid img-circle bg-primary d-flex align-items-center justify-content-center mx-auto" 
                             style="width: 100px; height: 100px; font-size: 48px; color: white;">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <h3 class="profile-username text-center">{{ $customer->fullName }}</h3>
                    <p class="text-muted text-center">Khách hàng</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b><i class="fas fa-envelope mr-2"></i> Email</b> 
                            <span class="float-right">{{ $customer->email }}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-phone mr-2"></i> Số điện thoại</b> 
                            <span class="float-right">{{ $customer->phone }}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-check-circle mr-2"></i> Xác thực Email</b> 
                            <span class="float-right">
                                @if($customer->isEmailVerified)
                                    <span class="badge badge-success">Đã xác thực</span>
                                @else
                                    <span class="badge badge-warning">Chưa xác thực</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-toggle-on mr-2"></i> Trạng thái</b> 
                            <span class="float-right">
                                @if($customer->is_active)
                                    <span class="badge badge-success">Hoạt động</span>
                                @else
                                    <span class="badge badge-danger">Bị khóa</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-calendar mr-2"></i> Ngày đăng ký</b> 
                            <span class="float-right">{{ $customer->createdDate ? $customer->createdDate->format('d/m/Y H:i') : 'N/A' }}</span>
                        </li>
                    </ul>

                    <a href="{{ route('admin.customers.edit', $customer->customerID) }}" class="btn btn-primary btn-block">
                        <i class="fas fa-edit"></i> Chỉnh sửa
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-default btn-block">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>

            {{-- Thống kê --}}
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar"></i> Thống kê</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 text-center">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng đơn hàng</span>
                                    <span class="info-box-number">{{ $orders->total() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">Tổng chi tiêu</span>
                                    <span class="info-box-number">{{ number_format($totalSpent, 0, ',', '.') }}đ</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 text-center">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">Địa chỉ</span>
                                    <span class="info-box-number">{{ $addresses->count() }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="info-box bg-light">
                                <div class="info-box-content">
                                    <span class="info-box-text">Đơn thành công</span>
                                    <span class="info-box-number">{{ $orders->where('status', 'Delivered')->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Địa chỉ & Đơn hàng --}}
        <div class="col-md-8">
            {{-- Danh sách Địa chỉ --}}
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</h3>
                </div>
                <div class="card-body">
                    @if($addresses->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Khách hàng chưa có địa chỉ nào.
                        </div>
                    @else
                        <div class="row">
                            @foreach($addresses as $address)
                                <div class="col-md-6 mb-3">
                                    <div class="card {{ $address->isDefault ? 'border-primary' : '' }}">
                                        <div class="card-body">
                                            @if($address->isDefault)
                                                <span class="badge badge-primary float-right">Mặc định</span>
                                            @endif
                                            <h5 class="card-title">
                                                <i class="fas fa-user"></i> {{ $address->recipientName }}
                                            </h5>
                                            <p class="card-text mb-1">
                                                <i class="fas fa-phone text-muted"></i> {{ $address->phone }}
                                            </p>
                                            <p class="card-text mb-1">
                                                <i class="fas fa-location-arrow text-muted"></i> 
                                                {{ $address->street }}
                                            </p>
                                            <p class="card-text mb-0">
                                                <i class="fas fa-map text-muted"></i> 
                                                {{ $address->city }}, {{ $address->country }}
                                                @if($address->postalCode)
                                                    ({{ $address->postalCode }})
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Danh sách Đơn hàng --}}
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shopping-cart"></i> Lịch sử đơn hàng</h3>
                </div>
                <div class="card-body p-0">
                    @if($orders->isEmpty())
                        <div class="alert alert-info m-3">
                            <i class="fas fa-info-circle"></i> Khách hàng chưa có đơn hàng nào.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Mã đơn</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Thanh toán</th>
                                        <th>Trạng thái</th>
                                        <th class="text-center">Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td><strong>#{{ $order->orderID }}</strong></td>
                                            <td>{{ $order->orderDate ? $order->orderDate->format('d/m/Y H:i') : 'N/A' }}</td>
                                            <td><strong>{{ number_format($order->total, 0, ',', '.') }}đ</strong></td>
                                            <td>
                                                @switch($order->paymentStatus)
                                                    @case('Paid')
                                                        <span class="badge badge-success">Đã thanh toán</span>
                                                        @break
                                                    @case('Unpaid')
                                                        <span class="badge badge-warning">Chưa thanh toán</span>
                                                        @break
                                                    @case('Refunded')
                                                        <span class="badge badge-info">Đã hoàn tiền</span>
                                                        @break
                                                @endswitch
                                                <br>
                                                <small class="text-muted">{{ $order->paymentMethod }}</small>
                                            </td>
                                            <td>
                                                @switch($order->status)
                                                    @case('Pending')
                                                        <span class="badge badge-secondary">Chờ xử lý</span>
                                                        @break
                                                    @case('Processing')
                                                        <span class="badge badge-info">Đang xử lý</span>
                                                        @break
                                                    @case('Shipped')
                                                        <span class="badge badge-primary">Đang giao</span>
                                                        @break
                                                    @case('Delivered')
                                                        <span class="badge badge-success">Đã giao</span>
                                                        @break
                                                    @case('Cancelled')
                                                        <span class="badge badge-danger">Đã hủy</span>
                                                        @break
                                                    @case('Refunded')
                                                        <span class="badge badge-dark">Đã hoàn</span>
                                                        @break
                                                    @case('Refund Requested')
                                                        <span class="badge badge-warning">Yêu cầu hoàn</span>
                                                        @break
                                                @endswitch
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('admin.orders.show', $order->orderID) }}" 
                                                   class="btn btn-sm btn-info" 
                                                   title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer">
                            {{ $orders->links('pagination::bootstrap-4') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .info-box {
        min-height: 70px;
        margin-bottom: 0;
    }
    .info-box-text {
        display: block;
        font-size: 12px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 18px;
    }
    .profile-user-img {
        border: 3px solid #adb5bd;
    }
</style>
@stop
