@extends('adminlte::page')

@section('title', 'Chi tiết Đơn hàng #' . $order->orderID)

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-file-invoice"></i> Chi tiết Đơn hàng #{{ $order->orderID }}
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.orders.index') }}">Đơn hàng</a></li>
                <li class="breadcrumb-item active">#{{ $order->orderID }}</li>
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

    <div class="row">
        {{-- Cột 1: Thông tin Khách hàng & Sản phẩm --}}
        <div class="col-lg-8">
            {{-- Card: Customer Info --}}
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i> Thông tin Khách hàng & Giao hàng
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h5>
                            <dl class="row">
                                <dt class="col-sm-5">Mã đơn:</dt>
                                <dd class="col-sm-7"><strong class="text-primary">#{{ $order->orderID }}</strong></dd>
                                
                                <dt class="col-sm-5">Ngày đặt:</dt>
                                <dd class="col-sm-7">{{ \Carbon\Carbon::parse($order->orderDate)->format('d/m/Y H:i') }}</dd>
                                
                                <dt class="col-sm-5">Khách hàng:</dt>
                                <dd class="col-sm-7">
                                    <strong>{{ $order->customer->fullName ?? 'Khách lẻ (ID: ' . $order->customerID . ')' }}</strong>
                                </dd>
                                
                                <dt class="col-sm-5">Email:</dt>
                                <dd class="col-sm-7">{{ $order->customer->email ?? 'N/A' }}</dd>
                                
                                <dt class="col-sm-5">Số điện thoại:</dt>
                                <dd class="col-sm-7">
                                    <i class="fas fa-phone"></i> {{ $order->address->phone }}
                                </dd>
                            </dl>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</h5>
                            <address>
                                <strong>{{ $order->address->recipientName ?? $order->customer->fullName }}</strong><br>
                                {{ $order->address->street }}<br>
                                {{ $order->address->city }}, {{ $order->address->country }}<br>
                                <i class="fas fa-phone"></i> {{ $order->address->phone }}
                            </address>
                            
                            @if($order->address->notes)
                                <div class="alert alert-info">
                                    <i class="fas fa-sticky-note"></i> <strong>Ghi chú:</strong><br>
                                    {{ $order->address->notes }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card: Products --}}
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 80px">Ảnh</th>
                                    <th>Sản phẩm</th>
                                    <th style="width: 20%">Biến thể</th>
                                    <th style="width: 12%" class="text-right">Giá</th>
                                    <th style="width: 8%" class="text-center">SL</th>
                                    <th style="width: 15%" class="text-right">Tổng</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->orderDetails as $item)
                                <tr>
                                    <td class="text-center">
                                        @php
                                            $product = $item->variant->product;
                                            $mainImage = $product->images->where('imageType', 'main')->first();
                                        @endphp
                                        @if ($mainImage)
                                            <img src="{{ asset('storage/' . $mainImage->imageUrl) }}" 
                                                 alt="{{ $product->productName }}" 
                                                 class="img-thumbnail"
                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                        @else
                                            <div class="text-muted">
                                                <i class="far fa-image fa-2x"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $item->variant->product->productName }}</strong>
                                        <br>
                                        <small class="text-muted">SKU: {{ $item->variant->sku }}</small>
                                    </td>
                                    <td>
                                        @foreach($item->variant->attributeValues as $attrValue)
                                            <span class="badge badge-secondary">{{ $attrValue->valueName }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-right">
                                        <strong>{{ number_format($item->price, 0, ',', '.') }}đ</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-right">
                                        <strong class="text-success">
                                            {{ number_format($item->price * $item->quantity, 0, ',', '.') }}đ
                                        </strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            @if($order->voucher)
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-tag"></i>
                                    <strong>Voucher:</strong> {{ $order->voucher->voucherCode }}
                                    <br>
                                    <small>{{ $order->voucher->voucherName }}</small>
                                </div>
                            @else
                                <p class="text-muted mb-0">
                                    <i class="fas fa-info-circle"></i> Không sử dụng voucher
                                </p>
                            @endif
                        </div>
                        <div class="col-md-6 text-right">
                            <h4>
                                <i class="fas fa-money-bill-wave text-success"></i>
                                Tổng thanh toán: 
                                <strong class="text-success">
                                    {{ number_format($order->total, 0, ',', '.') }} VNĐ
                                </strong>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cột 2: Trạng thái & Xử lý --}}
        <div class="col-lg-4">
            {{-- Card: Status Overview --}}
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-info-circle"></i> Trạng thái Đơn hàng
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">PT Thanh toán:</dt>
                        <dd class="col-sm-6">
                            <span class="badge badge-info">
                                <i class="fas fa-credit-card"></i> {{ $order->paymentMethod }}
                            </span>
                        </dd>
                        
                        <dt class="col-sm-6">TT Thanh toán:</dt>
                        <dd class="col-sm-6">
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
                        </dd>
                        
                        <dt class="col-sm-6">TT Đơn hàng:</dt>
                        <dd class="col-sm-6">
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
                        </dd>
                        
                        @if($order->shipping && $order->shipping->trackingCode)
                        <dt class="col-sm-6">Mã vận đơn:</dt>
                        <dd class="col-sm-6">
                            <strong class="text-primary">{{ $order->shipping->trackingCode }}</strong>
                        </dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Card: Update Status --}}
            @if(Gate::allows('admin') || Gate::allows('staff'))
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Cập nhật Trạng thái
                    </h3>
                </div>
                <div class="card-body">
                    @php
                        $isFinalOrCanceled = in_array($order->status, ['Cancelled', 'Refunded', 'Delivered']);
                        $currentTrackingCode = optional($order->shipping)->trackingCode;
                    @endphp

                    <form action="{{ route('admin.orders.update', $order) }}" method="POST" id="updateOrderForm">
                        @csrf
                        @method('PUT')
                        
                        {{-- Tracking Code --}}
                        @if(!$isFinalOrCanceled || $order->status == 'Shipped')
                        <div class="form-group">
                            <label for="trackingCode">
                                <i class="fas fa-barcode"></i> Mã vận đơn
                            </label>
                            <input type="text" 
                                   name="trackingCode" 
                                   id="trackingCode"
                                   class="form-control" 
                                   value="{{ old('trackingCode', $currentTrackingCode) }}" 
                                   placeholder="Nhập mã vận đơn">
                            <small class="form-text text-muted">
                                Mã này sẽ được lưu vào bảng Shipping
                            </small>
                        </div>
                        @endif
                        
                        {{-- Order Status --}}
                        <div class="form-group">
                            <label for="status">
                                <i class="fas fa-sync-alt"></i> Trạng thái Đơn hàng <span class="text-danger">*</span>
                            </label>
                            <select name="status" id="status" class="form-control" required>
                                @php 
                                    $disableProgress = in_array($order->status, ['Cancelled', 'Refunded']) ? 'disabled' : '';
                                    $statuses = [
                                        'Pending' => 'Chờ xử lý',
                                        'Processing' => 'Đang xử lý',
                                        'Shipped' => 'Đang giao hàng',
                                        'Delivered' => 'Đã giao',
                                        'Cancelled' => 'Đã hủy',
                                        'Refunded' => 'Đã hoàn tiền'
                                    ];
                                @endphp
                                
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" 
                                            {{ $order->status == $value ? 'selected' : '' }}
                                            {{ $disableProgress && !in_array($value, ['Cancelled', 'Refunded', 'Delivered']) ? 'disabled' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                Cancelled/Refunded sẽ hoàn lại tồn kho
                            </small>
                        </div>

                        {{-- Payment Status --}}
                        <div class="form-group">
                            <label for="paymentStatus">
                                <i class="fas fa-dollar-sign"></i> Trạng thái Thanh toán <span class="text-danger">*</span>
                            </label>
                            <select name="paymentStatus" id="paymentStatus" class="form-control" required>
                                <option value="Unpaid" {{ $order->paymentStatus == 'Unpaid' ? 'selected' : '' }}>
                                    Chưa thanh toán
                                </option>
                                <option value="Paid" {{ $order->paymentStatus == 'Paid' ? 'selected' : '' }}>
                                    Đã thanh toán
                                </option>
                                <option value="Refunded" {{ $order->paymentStatus == 'Refunded' ? 'selected' : '' }}>
                                    Đã hoàn tiền
                                </option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-warning btn-block">
                            <i class="fas fa-save"></i> Cập nhật Đơn hàng
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="card card-secondary">
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-lock"></i>
                        <strong>Giới hạn quyền</strong><br>
                        Bạn chỉ có quyền xem, không thể cập nhật trạng thái đơn hàng.
                    </div>
                </div>
            </div>
            @endif

            {{-- Card: Order Timeline --}}
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Lịch sử Đơn hàng
                    </h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="time-label">
                            <span class="bg-primary">
                                {{ \Carbon\Carbon::parse($order->orderDate)->format('d/m/Y') }}
                            </span>
                        </div>
                        
                        <div>
                            <i class="fas fa-shopping-cart bg-info"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="far fa-clock"></i> 
                                    {{ \Carbon\Carbon::parse($order->orderDate)->format('H:i') }}
                                </span>
                                <h3 class="timeline-header">Đơn hàng được tạo</h3>
                                <div class="timeline-body">
                                    Khách hàng: {{ $order->customer->fullName ?? 'Khách lẻ' }}
                                </div>
                            </div>
                        </div>
                        
                        @if($order->status != 'Pending')
                        <div>
                            <i class="fas fa-check bg-success"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Trạng thái: {{ $order->status }}</h3>
                            </div>
                        </div>
                        @endif
                        
                        <div>
                            <i class="far fa-clock bg-gray"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .card-outline {
        border-top: 3px solid;
    }
    
    .timeline {
        position: relative;
        margin: 0 0 30px 0;
        padding: 0;
        list-style: none;
    }
    
    .timeline:before {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #ddd;
        left: 31px;
        margin: 0;
        border-radius: 2px;
    }
</style>
@stop

@section('js')
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    console.log('✅ Order show page loaded');
    
    // Form confirmation
    $('#updateOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        const status = $('#status').val();
        const paymentStatus = $('#paymentStatus').val();
        
        Swal.fire({
            title: 'Xác nhận cập nhật?',
            html: `Bạn đang thay đổi:<br>
                   <strong>Trạng thái ĐH:</strong> ${status}<br>
                   <strong>Trạng thái TT:</strong> ${paymentStatus}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-save"></i> Cập nhật',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            customClass: {
                confirmButton: 'btn btn-warning mr-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    });
    
    // Auto dismiss alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@stop