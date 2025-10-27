{{-- resources/views/admin/product_discounts/edit.blade.php --}}
@extends('adminlte::page') 
{{-- Sửa lỗi: Phải sử dụng layout AdminLTE chuẩn --}}

@section('title', 'Chỉnh sửa Chương trình Giảm giá')

@section('content_header')
    <h1>Chỉnh sửa Chương trình Giảm giá: {{ $discount->discountName }}</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Thiết lập chi tiết Sale</h3>
    </div>
    
    {{-- SỬA LỖI ROUTE: Truyền tham số dưới dạng mảng key-value (tên_tham_số => giá_trị) --}}
    <form action="{{ route('admin.product-discounts.update', ['product_discount' => $discount->discountID]) }}" method="POST">
        @csrf
        @method('PUT') 
        <div class="card-body">
            
            {{-- Tên chương trình (SỬA TÊN CỘT) --}}
            <div class="form-group">
                <label for="discountName">Tên Chương trình Sale</label>
                <input type="text" name="discountName" id="discountName" class="form-control" 
                       value="{{ old('discountName', $discount->discountName) }}" required>
                @error('discountName')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            {{-- Loại giảm giá (SỬA TÊN CỘT) --}}
            <div class="form-group">
                <label for="discountType">Loại Giảm Giá</label>
                <select name="discountType" id="discountType" class="form-control" required>
                    <option value="percentage" {{ old('discountType', $discount->discountType) == 'percentage' ? 'selected' : '' }}>Phần trăm (%)</option>
                    <option value="fixed" {{ old('discountType', $discount->discountType) == 'fixed' ? 'selected' : '' }}>Cố định (VNĐ)</option>
                </select>
            </div>
            
            {{-- Giá trị và Max Discount (SỬA TÊN CỘT) --}}
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="discountValue">Giá trị Giảm</label>
                    <input type="number" name="discountValue" id="discountValue" class="form-control" 
                           value="{{ old('discountValue', $discount->discountValue) }}" min="0" required>
                    @error('discountValue')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6" id="maxDiscountAmountField">
                    <label for="maxDiscountAmount">Giảm tối đa (Chỉ dùng cho %)</label>
                    <input type="number" name="maxDiscountAmount" id="maxDiscountAmount" class="form-control" 
                           value="{{ old('maxDiscountAmount', $discount->maxDiscountAmount) }}" placeholder="Để trống nếu không giới hạn">
                    @error('maxDiscountAmount')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>

            {{-- Ngày bắt đầu/kết thúc (SỬA TÊN CỘT VÀ FORMAT DATETIME) --}}
            <div class="row">
                <div class="form-group col-md-6">
                    <label for="startDate">Ngày Bắt đầu</label>
                    <input type="datetime-local" name="startDate" id="startDate" class="form-control" 
                           value="{{ old('startDate', $discount->startDate ? \Carbon\Carbon::parse($discount->startDate)->format('Y-m-d\TH:i') : '') }}" required>
                    @error('startDate')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="form-group col-md-6">
                    <label for="endDate">Ngày Kết thúc</label>
                    <input type="datetime-local" name="endDate" id="endDate" class="form-control" 
                           value="{{ old('endDate', $discount->endDate ? \Carbon\Carbon::parse($discount->endDate)->format('Y-m-d\TH:i') : '') }}" required>
                    @error('endDate')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr>
            
            {{-- Đối tượng áp dụng (SỬA TÊN CỘT) --}}
            <div class="form-group">
                <label for="appliedToType">Áp dụng Giảm giá cho:</label>
                <select name="appliedToType" id="appliedToType" class="form-control" required>
                    @foreach(['category', 'brand', 'product', 'variant'] as $type)
                        <option value="{{ $type }}" {{ old('appliedToType', $discount->appliedToType) == $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
                @error('appliedToType')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            {{-- Input ID đối tượng áp dụng (SỬA TÊN CỘT) --}}
            <div class="form-group">
                <label for="appliedToID">ID Đối tượng áp dụng</label>
                <input type="number" name="appliedToID" id="appliedToID" class="form-control" 
                       value="{{ old('appliedToID', $discount->appliedToID) }}" required placeholder="Nhập ID Category/Brand/Product/Variant">
                @error('appliedToID')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            
            {{-- Trạng thái (SỬA TÊN CỘT) --}}
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input class="custom-control-input" type="checkbox" id="isActive" name="isActive" value="1" {{ old('isActive', $discount->isActive) ? 'checked' : '' }}>
                    <label for="isActive" class="custom-control-label">Kích hoạt chương trình ngay lập tức</label>
                </div>
            </div>

        </div>
        
        <div class="card-footer">
            <button type="submit" class="btn btn-warning">Cập nhật Sale</button>
            <a href="{{ route('admin.product-discounts.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@stop

@section('js')
<script>
// Code JS để xử lý ẩn/hiện Max Discount Amount
$(document).ready(function() {
    const maxDiscountAmountField = $('#maxDiscountAmountField');
    const discountTypeSelect = $('#discountType');

    function toggleMaxDiscount() {
        if (discountTypeSelect.val() === 'percentage') {
            maxDiscountAmountField.show();
            $('#maxDiscountAmount').prop('disabled', false);
        } else {
            maxDiscountAmountField.hide();
            $('#maxDiscountAmount').prop('disabled', true);
        }
    }
    
    toggleMaxDiscount();
    discountTypeSelect.on('change', toggleMaxDiscount);
});
</script>
@stop