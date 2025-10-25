<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="title">Tiêu đề Slider (Tùy chọn)</label>
            <input type="text" name="title" class="form-control" 
                   value="{{ old('title', $slider->title ?? '') }}">
        </div>
        <div class="form-group">
            <label for="backlink">Đường dẫn Liên kết (URL)</label>
            <input type="url" name="backlink" class="form-control" 
                   value="{{ old('backlink', $slider->backlink ?? '') }}">
        </div>
        <div class="form-group">
            <label for="notes">Ghi chú Nội bộ</label>
            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $slider->notes ?? '') }}</textarea>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="imageUrl">Hình ảnh Slider (*)</label>
            <input type="file" name="imageUrl" class="form-control-file" {{ isset($slider) ? '' : 'required' }}>
            <small class="text-muted">Kích thước khuyến nghị: 1920x600 px</small>
            @if(isset($slider) && $slider->imageUrl)
                <div class="mt-2">
                    <small>Ảnh hiện tại:</small><br>
                    <img src="{{ asset('storage/' . $slider->imageUrl) }}" style="width: 100%; max-height: 150px; object-fit: cover; border: 1px solid #ccc;">
                </div>
            @endif
        </div>
        <div class="form-group">
            <label for="status">Trạng thái (*)</label>
            @php $currentStatus = old('status', $slider->status ?? 'active'); @endphp
            <select name="status" class="form-control" required>
                <option value="active" {{ $currentStatus == 'active' ? 'selected' : '' }}>Hoạt động</option>
                <option value="inactive" {{ $currentStatus == 'inactive' ? 'selected' : '' }}>Ngưng hoạt động</option>
            </select>
        </div>
    </div>
</div>