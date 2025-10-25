@extends('adminlte::page')

@section('title', 'Quản lý Mã giảm giá')

@section('content_header')
    <h1>Mã giảm giá (Vouchers)</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Danh sách Mã giảm giá</h3>
            <div class="card-tools">
                <a href="{{ route('admin.vouchers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm Voucher
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Mã Code</th>
                        <th>Loại giảm</th>
                        <th>Giá trị</th>
                        <th>Đơn tối thiểu</th>
                        <th>Sử dụng (Max/Used)</th>
                        <th>Hiệu lực</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vouchers as $voucher)
                    <tr>
                        <td><strong>{{ $voucher->voucherCode }}</strong></td>
                        <td>{{ $voucher->discountType == 'percentage' ? 'Phần trăm' : 'Cố định' }}</td>
                        <td>
                            @if ($voucher->discountType == 'percentage')
                                {{ $voucher->discountValue }}% (Max: {{ number_format($voucher->maxDiscountAmount, 0, ',', '.') }} VNĐ)
                            @else
                                {{ number_format($voucher->discountValue, 0, ',', '.') }} VNĐ
                            @endif
                        </td>
                        <td>{{ number_format($voucher->minOrderValue, 0, ',', '.') }} VNĐ</td>
                        <td>{{ $voucher->usedCount }} / {{ $voucher->maxUsage }}</td>
                        <td>
                            {{ \Carbon\Carbon::parse($voucher->startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($voucher->endDate)->format('d/m/Y') }}
                        </td>
                        <td>
                            @if ($voucher->isActive && \Carbon\Carbon::parse($voucher->endDate)->isFuture())
                                <span class="badge badge-success">Hoạt động</span>
                            @else
                                <span class="badge badge-danger">Ngưng/Hết hạn</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="btn btn-info btn-xs">Sửa</a>
                            <form action="{{ route('admin.vouchers.destroy', $voucher) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này? Vouchers đã dùng vẫn giữ lại trong lịch sử đơn hàng.')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $vouchers->links('pagination::bootstrap-4') }}
        </div>
    </div>
@stop