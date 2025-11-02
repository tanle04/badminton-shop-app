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

        {{-- ⭐ SỬA PHẦN THỜI GIAN - Format đúng múi giờ Việt Nam --}}
        <div class="form-group">
            <div class="row">
                <div class="col-6">
                    <label for="startDate">Ngày bắt đầu (*)</label>
                    @php
                        $startDateFormatted = '';
                        if (isset($voucher->startDate) && $voucher->startDate) {
                            try {
                                $startDateFormatted = \Carbon\Carbon::parse($voucher->startDate)
                                    ->setTimezone('Asia/Ho_Chi_Minh')
                                    ->format('Y-m-d\TH:i');
                            } catch (\Exception $e) {
                                $startDateFormatted = '';
                            }
                        }
                    @endphp
                    <input type="datetime-local" 
                           name="startDate" 
                           id="startDate"
                           class="form-control" 
                           required
                           value="{{ old('startDate', $startDateFormatted) }}">
                    <small class="form-text text-muted">
                        <i class="fas fa-clock"></i> Múi giờ: Việt Nam (UTC+7)
                    </small>
                </div>
                <div class="col-6">
                    <label for="endDate">Ngày kết thúc (*)</label>
                    @php
                        $endDateFormatted = '';
                        if (isset($voucher->endDate) && $voucher->endDate) {
                            try {
                                $endDateFormatted = \Carbon\Carbon::parse($voucher->endDate)
                                    ->setTimezone('Asia/Ho_Chi_Minh')
                                    ->format('Y-m-d\TH:i');
                            } catch (\Exception $e) {
                                $endDateFormatted = '';
                            }
                        }
                    @endphp
                    <input type="datetime-local" 
                           name="endDate" 
                           id="endDate"
                           class="form-control" 
                           required
                           value="{{ old('endDate', $endDateFormatted) }}">
                    <small class="form-text text-muted">
                        <i class="fas fa-clock"></i> Múi giờ: Việt Nam (UTC+7)
                    </small>
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

{{-- ⭐ THÊM THÔNG TIN DEBUG (Chỉ hiển thị khi có $voucher) --}}
@if(isset($voucher) && $voucher->exists)
<div class="alert alert-info">
    <strong><i class="fas fa-info-circle"></i> Thông tin debug (Múi giờ):</strong><br>
    <small>
        - Thời gian hiện tại (Server): {{ \Carbon\Carbon::now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}<br>
        - Start Date (UTC): {{ $voucher->getAttributes()['startDate'] ?? 'N/A' }}<br>
        - Start Date (VN): {{ \Carbon\Carbon::parse($voucher->startDate)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}<br>
        - End Date (UTC): {{ $voucher->getAttributes()['endDate'] ?? 'N/A' }}<br>
        - End Date (VN): {{ \Carbon\Carbon::parse($voucher->endDate)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}
    </small>
</div>
@endif