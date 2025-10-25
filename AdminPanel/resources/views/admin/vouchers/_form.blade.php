<div class="row">

    {{-- CỘT 1: Thông tin cơ bản và Giới hạn sử dụng --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="voucherCode">Mã giảm giá (Code) (*)</label>
            <input type="text" name="voucherCode" class="form-control" required 
                   value="{{ old('voucherCode', $voucher->voucherCode ?? '') }}">
        </div>
        <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea name="description" class="form-control" rows="2">{{ old('description', $voucher->description ?? '') }}</textarea>
        </div>
        <div class="form-group">
            <label for="maxUsage">Số lần sử dụng tối đa (*)</label>
            <input type="number" name="maxUsage" class="form-control" required min="1"
                   value="{{ old('maxUsage', $voucher->maxUsage ?? 1) }}">
        </div>
        <div class="form-group">
            <label for="minOrderValue">Giá trị đơn hàng tối thiểu (*)</label>
            <input type="number" name="minOrderValue" class="form-control" required min="0" step="0.01"
                   value="{{ old('minOrderValue', $voucher->minOrderValue ?? 0.00) }}">
        </div>
    </div>
    {{-- HẾT CỘT 1 --}}

    {{-- CỘT 2: Giá trị Giảm và Thời hạn --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="discountType">Loại giảm giá (*)</label>
            @php $currentType = old('discountType', $voucher->discountType ?? 'fixed'); @endphp
            <select name="discountType" id="discountType" class="form-control" required>
                <option value="fixed" {{ $currentType == 'fixed' ? 'selected' : '' }}>Cố định (Giảm số tiền)</option>
                <option value="percentage" {{ $currentType == 'percentage' ? 'selected' : '' }}>Phần trăm (Giảm %)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="discountValue">Giá trị giảm (*)</label>
            <input type="number" name="discountValue" class="form-control" required min="1" step="0.01"
                   value="{{ old('discountValue', $voucher->discountValue ?? '') }}">
        </div>
        
        {{-- THÊM ID VÀ STYLE ĐỂ ẨN/HIỆN ĐỘNG --}}
        <div class="form-group" id="maxDiscountGroup" style="display: {{ $currentType == 'fixed' ? 'none' : 'block' }}">
            <label for="maxDiscountAmount">Giảm tối đa (Chỉ cho %)</label>
            <input type="number" name="maxDiscountAmount" id="maxDiscountAmount" class="form-control" min="0" step="0.01"
                   value="{{ old('maxDiscountAmount', $voucher->maxDiscountAmount ?? '') }}">
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-6">
                    <label for="startDate">Ngày bắt đầu (*)</label>
                    <input type="datetime-local" name="startDate" class="form-control" required
                           value="{{ old('startDate', optional($voucher->startDate ?? null)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-6">
                    <label for="endDate">Ngày kết thúc (*)</label>
                    <input type="datetime-local" name="endDate" class="form-control" required
                           value="{{ old('endDate', optional($voucher->endDate ?? null)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>
        </div>
    </div>
    {{-- HẾT CỘT 2 --}}
</div> {{-- KẾT THÚC ROW CHÍNH --}}

{{-- Checkbox (Phần này nằm ngoài row/col) --}}
<div class="form-group">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="isActive" value="1" id="isActive"
            {{ old('isActive', $voucher->isActive ?? 1) ? 'checked' : '' }}>
        <label class="form-check-label" for="isActive">Kích hoạt (Active)</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="isPrivate" value="1" id="isPrivate"
            {{ old('isPrivate', $voucher->isPrivate ?? 0) ? 'checked' : '' }}>
        <label class="form-check-label" for="isPrivate">Voucher Riêng (Private - chỉ dùng cho khách hàng cụ thể)</label>
    </div>
</div>

@section('js')
    @parent
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
            
            // Chạy lần đầu để thiết lập trạng thái chính xác (dành cho form edit)
            $('#discountType').trigger('change');
        });
    </script>
@endsection