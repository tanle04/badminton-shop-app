@extends('adminlte::page')

@section('title', 'Tạo Mã giảm giá Mới')

@section('content_header')
    <h1>Tạo Mã giảm giá Mới</h1>
@stop

@section('content')
    <form action="{{ route('admin.vouchers.store') }}" method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Thông tin Mã giảm giá</h3></div>
            <div class="card-body">
                {{-- Hiển thị thời gian hiện tại --}}
                <div class="alert alert-info">
                    <i class="fas fa-clock"></i> Thời gian hiện tại (Việt Nam): 
                    <strong>{{ \Carbon\Carbon::now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}</strong>
                </div>
                
                {{-- Bao gồm form chung, truyền biến $voucher là null/trống --}}
                @include('admin.vouchers._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Lưu Voucher</button>
                <a href="{{ route('admin.vouchers.index') }}" class="btn btn-default">Hủy</a>
            </div>
        </div>
    </form>
@stop

@section('js')
    <script>
        $(document).ready(function() {
            console.log('✅ Form create ready - Timezone: Asia/Ho_Chi_Minh');
            
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
            
            // Chạy lần đầu để thiết lập trạng thái chính xác
            $('#discountType').trigger('change');
            
            // ⭐ Validation: Kiểm tra ngày kết thúc phải sau ngày bắt đầu
            $('form').on('submit', function(e) {
                const startDate = new Date($('#startDate').val());
                const endDate = new Date($('#endDate').val());
                
                if (endDate <= startDate) {
                    e.preventDefault();
                    alert('⚠️ Ngày kết thúc phải sau ngày bắt đầu!');
                    return false;
                }
                
                console.log('✅ Form validation passed');
                console.log('Start:', startDate);
                console.log('End:', endDate);
                return true;
            });
        });
    </script>
@endsection