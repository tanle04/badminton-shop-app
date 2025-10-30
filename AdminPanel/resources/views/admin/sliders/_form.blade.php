<div class="row">
    {{-- CỘT 1 --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="title">Tiêu đề Slider <small class="text-muted">(Tùy chọn)</small></label>
            <input type="text" 
                   name="title" 
                   id="title"
                   class="form-control @error('title') is-invalid @enderror" 
                   value="{{ old('title', $slider->title ?? '') }}"
                   placeholder="VD: Banner giảm giá 50%">
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="backlink">Đường dẫn Liên kết (URL) <small class="text-muted">(Tùy chọn)</small></label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                </div>
                <input type="url" 
                       name="backlink" 
                       id="backlink"
                       class="form-control @error('backlink') is-invalid @enderror" 
                       value="{{ old('backlink', $slider->backlink ?? '') }}"
                       placeholder="https://example.com/products">
            </div>
            @error('backlink')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="form-text text-muted">
                <i class="fas fa-info-circle"></i> 
                URL sẽ được mở khi click vào slider
            </small>
        </div>

        <div class="form-group">
            <label for="notes">Ghi chú Nội bộ <small class="text-muted">(Tùy chọn)</small></label>
            <textarea name="notes" 
                      id="notes"
                      class="form-control @error('notes') is-invalid @enderror" 
                      rows="4"
                      placeholder="Ghi chú cho nội bộ, không hiển thị ra ngoài...">{{ old('notes', $slider->notes ?? '') }}</textarea>
            @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="status">Trạng thái <span class="text-danger">*</span></label>
            @php $currentStatus = old('status', $slider->status ?? 'active'); @endphp
            <select name="status" 
                    id="status"
                    class="form-control @error('status') is-invalid @enderror" 
                    required>
                <option value="active" {{ $currentStatus == 'active' ? 'selected' : '' }}>
                    ✅ Hoạt động (Hiển thị trên trang chủ)
                </option>
                <option value="inactive" {{ $currentStatus == 'inactive' ? 'selected' : '' }}>
                    ⛔ Tạm ngưng (Ẩn slider)
                </option>
            </select>
            @error('status')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    {{-- CỘT 2 --}}
    <div class="col-md-6">
        <div class="form-group">
            <label for="imageUrl">
                Hình ảnh Slider 
                <span class="text-danger">{{ isset($slider) ? '' : '*' }}</span>
            </label>
            
            <div class="custom-file">
                <input type="file" 
                       name="imageUrl" 
                       id="imageUrl"
                       class="custom-file-input @error('imageUrl') is-invalid @enderror" 
                       accept="image/*"
                       {{ isset($slider) ? '' : 'required' }}>
                <label class="custom-file-label" for="imageUrl">
                    Chọn hình ảnh...
                </label>
            </div>
            
            @error('imageUrl')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            
            <small class="form-text text-muted">
                <i class="fas fa-image"></i> 
                Kích thước khuyến nghị: <strong>1920x600 px</strong><br>
                <i class="fas fa-file-image"></i> 
                Định dạng: JPEG, JPG, PNG, GIF, WEBP<br>
                <i class="fas fa-hdd"></i> 
                Dung lượng tối đa: <strong>5 MB</strong>
            </small>
        </div>

        {{-- PREVIEW ẢNH CŨ (Nếu đang edit) --}}
        @if(isset($slider) && $slider->imageUrl)
            <div class="form-group">
                <label>Ảnh hiện tại:</label>
                <div class="preview-container">
                    <img src="{{ asset('storage/' . $slider->imageUrl) }}" 
                         id="current-preview"
                         class="img-fluid img-thumbnail"
                         style="width: 100%; max-height: 300px; object-fit: cover;">
                    <div class="preview-overlay">
                        <span class="badge badge-secondary">Ảnh hiện tại</span>
                    </div>
                </div>
            </div>
        @endif

        {{-- PREVIEW ẢNH MỚI --}}
        <div class="form-group" id="new-preview-container" style="display: none;">
            <label>Ảnh mới (Preview):</label>
            <div class="preview-container">
                <img src="" 
                     id="new-preview"
                     class="img-fluid img-thumbnail"
                     style="width: 100%; max-height: 300px; object-fit: cover;">
                <div class="preview-overlay">
                    <span class="badge badge-success">Ảnh mới</span>
                    <button type="button" class="btn btn-sm btn-danger" id="btn-remove-preview">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@section('css')
    @parent
    <style>
        .preview-container {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .preview-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .preview-container img {
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .preview-container:hover img {
            transform: scale(1.02);
        }
        
        .custom-file-label::after {
            content: "Chọn file";
        }
    </style>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function() {
            // Preview ảnh khi chọn file
            $('#imageUrl').on('change', function(e) {
                const file = e.target.files[0];
                
                if (file) {
                    // Check file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire({
                            icon: 'error',
                            title: 'File quá lớn!',
                            text: 'Kích thước ảnh không được vượt quá 5 MB'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    // Check file type
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!validTypes.includes(file.type)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Định dạng không hợp lệ!',
                            text: 'Chỉ chấp nhận: JPEG, JPG, PNG, GIF, WEBP'
                        });
                        $(this).val('');
                        return;
                    }
                    
                    // Update label
                    $('.custom-file-label').text(file.name);
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#new-preview').attr('src', e.target.result);
                        $('#new-preview-container').fadeIn();
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Remove preview
            $('#btn-remove-preview').on('click', function() {
                $('#imageUrl').val('');
                $('.custom-file-label').text('Chọn hình ảnh...');
                $('#new-preview-container').fadeOut();
                $('#new-preview').attr('src', '');
            });
            
            // Update file input label khi chọn file
            $('.custom-file-input').on('change', function() {
                const fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });
        });
    </script>
@endsection