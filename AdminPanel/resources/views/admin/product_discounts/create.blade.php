{{-- resources/views/admin/product_discounts/create.blade.php --}}
@extends('adminlte::page') 
{{-- Sửa lỗi: Phải sử dụng layout AdminLTE chuẩn --}}

@section('title', 'Tạo Chương trình Giảm giá mới')

@section('content_header')
    <h1>Tạo Chương trình Giảm giá mới</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Thiết lập chi tiết Sale</h3>
    </div>
    
    {{-- Form action trỏ về route API admin.product-discounts.store --}}
    <form action="{{ route('admin.product-discounts.store') }}" method="POST">
        @csrf
        <div class="card-body">
            
            {{-- Tên chương trình --}}
            <div class="form-group">
                <label for="discountName">Tên Chương trình Sale</label>
                <input type="text" name="discountName" id="discountName" class="form-control" required>
                {{-- Dùng Blade để hiển thị lỗi validation --}}
                @error('discountName')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            {{-- Loại giảm giá --}}
            <div class="form-group">
                <label for="discountType">Loại Giảm Giá</label>
                <select name="discountType" id="discountType" class="form-control" required>
                    <option value="percentage">Phần trăm (%)</option>
                    <option value="fixed">Cố định (VNĐ)</option>
                </select>
                @error('discountType')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            
            {{-- Giá trị và Max Discount --}}
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="discountValue">Giá trị Giảm</label>
                    <input type="number" name="discountValue" id="discountValue" class="form-control" min="0" required>
                    @error('discountValue')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6" id="maxDiscountAmountField">
                    <label for="maxDiscountAmount">Giảm tối đa (Chỉ dùng cho %)</label>
                    <input type="number" name="maxDiscountAmount" id="maxDiscountAmount" class="form-control" placeholder="Để trống nếu không giới hạn">
                    @error('maxDiscountAmount')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Ngày bắt đầu/kết thúc --}}
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="startDate">Ngày Bắt đầu</label>
                    <input type="datetime-local" name="startDate" id="startDate" class="form-control" required>
                    @error('startDate')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="endDate">Ngày Kết thúc</label>
                    <input type="datetime-local" name="endDate" id="endDate" class="form-control" required>
                    @error('endDate')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr>
            
            {{-- Đối tượng áp dụng --}}
            <div class="form-group">
                <label for="appliedToType">Áp dụng Giảm giá cho:</label>
                <select name="appliedToType" id="appliedToType" class="form-control" required>
                    <option value="category">Danh mục sản phẩm (Category)</option>
                    <option value="brand">Thương hiệu (Brand)</option>
                    <option value="product">Sản phẩm cụ thể (Product)</option>
                    <option value="variant">Biến thể sản phẩm (Variant)</option>
                </select>
                @error('appliedToType')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            {{-- Input ID đối tượng áp dụng --}}
            <div class="form-group">
                <label for="appliedToID">ID Đối tượng áp dụng</label>
                {{-- KHÔNG DÙNG selectbox với $products/$brands/$categories được truyền từ Controller ở đây
                     vì form này chỉ cần appliedToID. Ta sẽ dùng JS/Ajax để dynamic hóa input này. --}}
                <input type="number" name="appliedToID" id="appliedToID" class="form-control" required placeholder="Nhập ID Category/Brand/Product/Variant">
                @error('appliedToID')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            
            {{-- Trạng thái --}}
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input class="custom-control-input" type="checkbox" id="isActive" name="isActive" value="1" checked>
                    <label for="isActive" class="custom-control-label">Kích hoạt chương trình ngay lập tức</label>
                </div>
            </div>

        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-success">Tạo Chương trình Sale</button>
            <a href="{{ route('admin.product-discounts.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Logic ẩn/hiện Max Discount Amount dựa trên loại giảm giá
    const maxDiscountAmountField = $('#maxDiscountAmountField');
    const discountTypeSelect = $('#discountType');

    function toggleMaxDiscount() {
        if (discountTypeSelect.val() === 'percentage') {
            maxDiscountAmountField.show();
            $('#maxDiscountAmount').prop('disabled', false);
        } else {
            maxDiscountAmountField.hide();
            $('#maxDiscountAmount').prop('disabled', true).val('');
        }
    }
    
    toggleMaxDiscount();
    discountTypeSelect.on('change', toggleMaxDiscount);

    // Bạn có thể thêm AJAX để load selectbox động cho appliedToID ở đây
    // Dựa trên appliedToType được chọn.
});
</script>
@stop