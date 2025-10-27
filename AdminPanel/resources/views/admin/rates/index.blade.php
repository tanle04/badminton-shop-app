@extends('adminlte::page')

@section('title', 'Quản lý Mức phí Vận chuyển')

@section('content_header')
    <h1>Quản lý Mức phí Vận chuyển (Shipping Rates)</h1>
@stop

@section('content')
<div class="card shadow mb-4">
    <div class="card-header">
        <h3 class="card-title">Danh sách Mức phí Dịch vụ</h3>
        <div class="card-tools">
            <a href="{{ route('admin.rates.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Thêm Rate mới
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Đơn vị Vận chuyển</th>
                        <th>Tên Dịch vụ</th>
                        <th>Phí (VND)</th>
                        <th>Thời gian Dự kiến</th>
                        <th style="width: 150px">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rates as $rate)
                    <tr>
                        <td>{{ $rate->rateID }}</td>
                        {{-- Sử dụng mối quan hệ 'carrier' đã định nghĩa trong ShippingRate Model --}}
                        <td>{{ $rate->carrier->carrierName ?? 'N/A' }}</td>
                        <td>{{ $rate->serviceName }}</td>
                        <td>{{ number_format($rate->price) }} đ</td>
                        <td>{{ $rate->estimatedDelivery }}</td>
                        <td>
                            <a href="{{ route('admin.rates.edit', $rate) }}" class="btn btn-info btn-xs">Sửa</a>
                            <form action="{{ route('admin.rates.destroy', $rate) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Bạn có chắc chắn muốn xóa Rate dịch vụ này không?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer clearfix">
        {{ $rates->links('pagination::bootstrap-4') }}
    </div>
</div>
@stop