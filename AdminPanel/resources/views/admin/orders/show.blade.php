@extends('adminlte::page')

@section('title', 'Chi tiết Đơn hàng #' . $order->orderID)

@section('content_header')
    <h1>Chi tiết Đơn hàng #{{ $order->orderID }}</h1>
@stop

@section('content')
    <div class="row">
        {{-- Cột 1: Thông tin Khách hàng & Sản phẩm --}}
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header"><h3 class="card-title">Thông tin Khách hàng & Giao hàng</h3></div>
                <div class="card-body">
                    <p><strong>Ngày đặt hàng:</strong> {{ \Carbon\Carbon::parse($order->orderDate)->format('d/m/Y H:i') }}</p>
                    <hr>
                    <p><strong>Khách hàng:</strong> {{ $order->customer->fullName ?? 'Khách lẻ (ID: ' . $order->customerID . ')' }}</p>
                    <p><strong>Email:</strong> {{ $order->customer->email ?? 'N/A' }}</p>
                    <p><strong>SĐT:</strong> {{ $order->address->phone }}</p>
                    <hr>
                    <p><strong>Địa chỉ:</strong> {{ $order->address->street }}, {{ $order->address->city }}, {{ $order->address->country }}</p>
                </div>
            </div>

            <div class="card card-info">
                <div class="card-header"><h3 class="card-title">Sản phẩm đã đặt</h3></div>
                <div class="card-body p-0">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Ảnh</th>
                                <th>Sản phẩm</th>
                                <th>Biến thể</th>
                                <th>Giá</th>
                                <th>SL</th>
                                <th>Tổng</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->orderDetails as $item)
                            <tr>
                                <td>
                                    @php
                                        $product = $item->variant->product;
                                        // Giả định mối quan hệ orderDetails.variant.product.images đã được load
                                        $mainImage = $product->images->where('imageType', 'main')->first();
                                    @endphp
                                    @if ($mainImage)
                                        <img src="{{ asset('storage/' . $mainImage->imageUrl) }}" alt="{{ $product->productName }}" style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                        <i class="far fa-image"></i>
                                    @endif
                                </td>
                                <td>{{ $item->variant->product->productName }}</td>
                                <td>
                                    @foreach($item->variant->attributeValues as $attrValue)
                                        <span class="badge badge-secondary">{{ $attrValue->valueName }}</span>
                                    @endforeach
                                    (SKU: {{ $item->variant->sku }})
                                </td>
                                <td>{{ number_format($item->price, 0, ',', '.') }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-right">
                    @if($order->voucher)
                        <p class="text-danger">Giảm giá Voucher: {{ $order->voucher->voucherCode }}</p>
                    @endif
                    <h4>Tổng thanh toán: {{ number_format($order->total, 0, ',', '.') }} VNĐ</h4>
                </div>
            </div>
        </div>

        {{-- Cột 2: Trạng thái & Xử lý --}}
        <div class="col-md-4">
            <div class="card card-warning">
                <div class="card-header"><h3 class="card-title">Xử lý Đơn hàng</h3></div>
                <div class="card-body">
                    <p><strong>PT Thanh toán:</strong> {{ $order->paymentMethod }}</p>
                    <p><strong>Trạng thái TT:</strong> 
                        <span class="badge @if($order->paymentStatus == 'Paid') badge-success @elseif($order->paymentStatus == 'Unpaid') badge-warning @else badge-danger @endif">{{ $order->paymentStatus }}</span>
                    </p>
                    <p><strong>Trạng thái ĐH:</strong> 
                        <span class="badge 
                            @if($order->status == 'Delivered') badge-success 
                            @elseif($order->status == 'Processing' || $order->status == 'Shipped') badge-info 
                            @elseif($order->status == 'Cancelled' || $order->status == 'Refunded') badge-danger 
                            @else badge-secondary @endif">{{ $order->status }}
                        </span>
                    </p>

                    @if(Gate::allows('admin') || Gate::allows('staff'))
                        <hr>
                        <h5>Cập nhật Trạng thái</h5>
                        
                        {{-- Logic Khóa Trạng thái cho UX --}}
                        @php
                            $isFinalOrCanceled = in_array($order->status, ['Cancelled', 'Refunded', 'Delivered']);
                            $currentTrackingCode = optional($order->shipping)->trackingCode;
                        @endphp

                        <form action="{{ route('admin.orders.update', $order) }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            {{-- MÃ VẬN ĐƠN (Chỉ hiển thị khi đang xử lý/vận chuyển) --}}
                            @if(!$isFinalOrCanceled || $order->status == 'Shipped')
                            <div class="form-group">
                                <label for="trackingCode">Mã vận đơn</label>
                                <input type="text" name="trackingCode" class="form-control" 
                                       value="{{ old('trackingCode', $currentTrackingCode) }}" 
                                       placeholder="Nhập mã vận đơn nếu Ship">
                                <small class="text-muted">Mã này sẽ được lưu vào bảng Shipping.</small>
                            </div>
                            @endif
                            
                            <div class="form-group">
                                <label for="status">Trạng thái Đơn hàng</label>
                                <select name="status" class="form-control" required>
                                    {{-- Khóa các trạng thái tiến trình nếu đã ở Delivered/Cancelled/Refunded --}}
                                    @php $disableProgress = in_array($order->status, ['Cancelled', 'Refunded']) ? 'disabled' : ''; @endphp
                                    
                                    <option value="Pending" {{ $order->status == 'Pending' ? 'selected' : '' }} {{ $disableProgress }}>Pending</option>
                                    <option value="Processing" {{ $order->status == 'Processing' ? 'selected' : '' }} {{ $disableProgress }}>Processing</option>
                                    <option value="Shipped" {{ $order->status == 'Shipped' ? 'selected' : '' }} {{ $disableProgress }}>Shipped</option>
                                    <option value="Delivered" {{ $order->status == 'Delivered' ? 'selected' : '' }}>Delivered</option>
                                    <option value="Cancelled" {{ $order->status == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    <option value="Refunded" {{ $order->status == 'Refunded' ? 'selected' : '' }}>Refunded</option>
                                </select>
                                <small class="text-danger">Thay đổi thành Cancelled/Refunded sẽ hoàn lại tồn kho.</small>
                            </div>

                            <div class="form-group">
                                <label for="paymentStatus">Trạng thái Thanh toán</label>
                                <select name="paymentStatus" class="form-control" required>
                                    <option value="Unpaid" {{ $order->paymentStatus == 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="Paid" {{ $order->paymentStatus == 'Paid' ? 'selected' : '' }}>Paid</option>
                                    <option value="Refunded" {{ $order->paymentStatus == 'Refunded' ? 'selected' : '' }}>Refunded</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-warning btn-block">Cập nhật</button>
                        </form>
                    @else
                        <div class="alert alert-info mt-3">
                            Bạn chỉ có quyền xem, không thể cập nhật trạng thái đơn hàng.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop