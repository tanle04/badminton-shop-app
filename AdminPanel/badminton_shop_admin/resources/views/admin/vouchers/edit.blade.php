@extends('adminlte::page')

@section('title', 'Chỉnh sửa Mã giảm giá: ' . $voucher->voucherCode)

@section('content_header')
    <h1>Chỉnh sửa Mã giảm giá: {{ $voucher->voucherCode }}</h1>
@stop

@section('content')
    <form action="{{ route('admin.vouchers.update', $voucher) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card card-info">
            <div class="card-header"><h3 class="card-title">Thông tin Mã giảm giá</h3></div>
            <div class="card-body">
                {{-- Hiển thị số lần đã dùng --}}
                <div class="alert alert-warning">
                    Đã sử dụng: <strong>{{ $voucher->usedCount }}</strong> / {{ $voucher->maxUsage }} lần. (Mã đã dùng không thể thay đổi Code.)
                </div>
                {{-- BAO GỒM FORM CHUNG (đã xóa JS bên trong) --}}
                @include('admin.vouchers._form', ['voucher' => $voucher])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Cập nhật Voucher</button>
                <a href="{{ route('admin.vouchers.index') }}" class="btn btn-default">Hủy</a>
            </div>
        </div>
    </form>
@stop

{{-- Đặt JavaScript tại vị trí này để nó được tải sau nội dung chính --}}
@section('js')
    <script>
        $(document).ready(function() {
            // Hàm xử lý ẩn hiện trường Giảm tối đa
            $('#discountType').change(function() {
                const selectedType = $(this).val();
                const $maxDiscountGroup = $('#maxDiscountGroup');
                const $maxDiscountAmount = $('#maxDiscountAmount');
                
                if (selectedType === 'percentage') {
                    $maxDiscountGroup.slideDown(); // Hiện field
                    $maxDiscountAmount.attr('required', true); // Bắt buộc nhập khi là %
                } else {
                    $maxDiscountGroup.slideUp(); // Ẩn field
                    $maxDiscountAmount.attr('required', false);
                    $maxDiscountAmount.val(''); // Xóa giá trị khi ẩn
                }
            });
            
            // Chạy lần đầu để thiết lập trạng thái chính xác (cần cho form edit)
            $('#discountType').trigger('change');
        });
    </script>
@endsection