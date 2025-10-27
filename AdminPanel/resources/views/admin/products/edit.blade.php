@extends('adminlte::page')

@section('title', 'Chỉnh sửa Sản phẩm: ' . $product->productName)

@section('content_header')
    <h1>Chỉnh sửa Sản phẩm: {{ $product->productName }}</h1>
@stop

@section('content')
    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="row">
            {{-- Cột 1: Thông tin cơ bản và Biến thể --}}
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Thông tin cơ bản</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="productName">Tên sản phẩm (*)</label>
                            <input type="text" name="productName" class="form-control" value="{{ old('productName', $product->productName) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Mô tả sản phẩm</label>
                            <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="categoryID">Danh mục (*)</label>
                            {{-- THAY ĐỔI: Thêm ID để JS bắt sự kiện --}}
                            <select name="categoryID" id="categoryID" class="form-control" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->categoryID }}" {{ $product->categoryID == $category->categoryID ? 'selected' : '' }}>
                                        {{ $category->categoryName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="brandID">Thương hiệu (*)</label>
                            <select name="brandID" class="form-control" required>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->brandID }}" {{ $product->brandID == $brand->brandID ? 'selected' : '' }}>
                                        {{ $brand->brandName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Card quản lý Biến thể (Variants) --}}
                <div class="card card-info">
                    <div class="card-header"><h3 class="card-title">Quản lý Biến thể (Variants)</h3></div>
                    <div class="card-body">
                        <p class="text-danger">**LƯU Ý:** Để đơn giản hóa việc sửa đổi Variants, hệ thống sẽ xóa các Variants cũ và tạo lại Variants mới dựa trên Ma trận thuộc tính được chọn bên dưới.</p>
                        
                        {{-- Phần chọn Thuộc tính (Attributes) đã được gán --}}
                        <div class="form-group" id="attribute-selection-area">
                            <label>Chọn các Thuộc tính áp dụng:</label>
                            @php
                                // Lấy tất cả valueID đã được gán cho TẤT CẢ biến thể của sản phẩm này (để check)
                                $currentValueIds = $product->variants->pluck('attributeValues')->flatten()->pluck('valueID')->unique()->toArray();
                            @endphp
                            
                            @foreach ($attributes as $attribute)
                                <div class="attribute-group" 
                                    data-attribute-id="{{ $attribute->attributeID }}" 
                                    data-attribute-name="{{ $attribute->attributeName }}"
                                    id="attribute-{{ $attribute->attributeID }}" style="display: none;">
                                    
                                    <strong>{{ $attribute->attributeName }}</strong>:
                                    @foreach ($attribute->values as $value)
                                        <div class="form-check form-check-inline attribute-value-item" 
                                            data-value-id="{{ $value->valueID }}">
                                            
                                            <input class="form-check-input attribute-checkbox" type="checkbox" 
                                                name="attribute_values_temp[]" 
                                                data-attribute-id="{{ $attribute->attributeID }}"
                                                data-attribute-value-name="{{ $value->valueName }}"
                                                value="{{ $value->valueID }}" 
                                                {{ in_array($value->valueID, $currentValueIds) ? 'checked' : '' }}
                                                onchange="generateVariants()">
                                            <label class="form-check-label">{{ $value->valueName }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <hr class="attribute-hr" style="display: none;">
                            @endforeach
                            <small class="text-danger">Chọn/bỏ chọn thuộc tính sẽ tạo lại Ma trận Biến thể bên dưới.</small>
                        </div>

                        <hr>
                        
                        {{-- Vùng hiển thị Ma trận Biến thể Động --}}
                        <h5 id="variant-matrix-title">Ma trận Biến thể được tạo:</h5>
                        <div id="variant-matrix-area">
                            <p class="text-info">Đang tải Variants hiện tại...</p>
                            {{-- Variants sẽ được load và tạo bởi JavaScript/jQuery --}}
                        </div>
                        
                    </div>
                </div>
            </div>

            {{-- Cột 2: Hình ảnh --}}
            <div class="col-md-4">
                <div class="card card-success">
                    <div class="card-header"><h3 class="card-title">Hình ảnh Sản phẩm</h3></div>
                    <div class="card-body">
                        {{-- Hiển thị ảnh hiện tại và Nút Xóa --}}
                        <label>Ảnh hiện tại:</label>
                        <div class="row mb-3" id="current-images-row">
                            @foreach ($product->images as $image)
                                <div class="col-4 text-center image-item-{{ $image->imageID }}">
                                    <img src="{{ asset('storage/' . $image->imageUrl) }}" style="width: 100%; height: 80px; object-fit: cover; border: 1px solid #ccc;">
                                    <p class="text-sm mt-1">{{ $image->imageType == 'main' ? 'CHÍNH' : 'Gallery' }}</p>
                                    
                                    {{-- Nút Xóa Ảnh --}}
                                    <button type="button" 
                                            class="btn btn-danger btn-xs btn-remove-image" 
                                            data-image-id="{{ $image->imageID }}">
                                        Xóa
                                    </button>
                                </div>
                            @endforeach
                        </div>

                        {{-- Form upload ảnh mới / thay thế --}}
                        <div class="form-group">
                            <label for="new_main_image">Cập nhật Ảnh Chính</label>
                            <input type="file" name="images[main]" class="form-control-file">
                            <small class="text-muted">Chọn file mới để thay thế ảnh Chính.</small>
                        </div>
                        <div class="form-group">
                            <label for="new_gallery_images">Thêm Ảnh Thư viện mới</label>
                            <input type="file" name="images[gallery][]" class="form-control-file" multiple>
                            <small class="text-muted">Các ảnh này sẽ được thêm vào thư viện hiện tại.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Cập nhật Sản phẩm</button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-default">Hủy</a>
    </form>
@stop

@section('js')
{{-- KHẮC PHỤC LỖI TOASTR: AdminLTE đang không chèn script, ta thêm thủ công --}}
<link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}">
<script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>

<script>
    const CURRENT_VARIANTS = @json($product->variants->keyBy(function($item) {
        // Tạo khóa dựa trên tổ hợp valueID để tra cứu nhanh
        return collect($item->attributeValues)->pluck('valueID')->sort()->implode('_');
    }));
    
    // Ánh xạ CategoryID -> Attribute IDs và Value IDs cần hiển thị (Sử dụng lại từ create.blade.php)
    const CATEGORY_ATTRIBUTES = {
        1: { show: [2, 3], filter: {} }, 
        2: { show: [1], filter: { 1: [1, 2, 3, 4, 5, 6, 7] } }, 
        3: { show: [1], filter: { 1: [8, 9, 10] } } 
    };

    $(document).ready(function() {
        // Lắng nghe sự kiện thay đổi Category
        $('#categoryID').on('change', function() {
            filterAttributes($(this).val());
            generateVariants(); 
        });

        // Khởi tạo trạng thái ban đầu: Lọc thuộc tính và Tạo Variants
        filterAttributes($('#categoryID').val());
        generateVariants();
        
        // Logic xóa ảnh (từ bước trước)
        $('.btn-remove-image').on('click', function() {
            const imageId = $(this).data('image-id');
            const productId = '{{ $product->productID }}';

            if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) {
                $.ajax({
                    url: `/admin/products/${productId}/images/${imageId}`,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}',
                    },
                    success: function(response) {
                        if (response.success) {
                            $(`.image-item-${imageId}`).remove();
                            toastr.success(response.message || 'Ảnh đã được xóa.');
                        } else {
                            toastr.error(response.message || 'Lỗi khi xóa ảnh.');
                        }
                    },
                    error: function(xhr) {
                        const response = JSON.parse(xhr.responseText);
                        toastr.error(response.message || 'Lỗi server khi xóa ảnh.');
                    }
                });
            }
        });
    });
    
    // Hàm lọc thuộc tính theo Category (Không thay đổi)
    function filterAttributes(categoryId) {
        categoryId = parseInt(categoryId);
        const config = CATEGORY_ATTRIBUTES[categoryId];
        
        $('.attribute-group').hide();
        $('.attribute-hr').hide();
        
        if (!config || config.show.length === 0) return;
        
        config.show.forEach(attrId => {
            const $group = $('#attribute-' + attrId);
            $group.show();
            $group.next('.attribute-hr').show();
            
            if (config.filter && config.filter[attrId]) {
                const allowedValueIds = config.filter[attrId];
                
                $group.find('.attribute-value-item').each(function() {
                    const valueId = parseInt($(this).data('value-id'));
                    if (allowedValueIds.includes(valueId)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                        // KHÔNG bỏ check ở đây, giữ lại trạng thái DB
                    }
                });
            } else {
                $group.find('.attribute-value-item').show();
            }
        });
    }

    // Hàm tạo Ma trận Biến thể (Đã sửa đổi để điền sẵn dữ liệu cũ)
    function generateVariants() {
        const selectedValues = {}; 
        // Chỉ lấy các checkbox được check VÀ đang được hiển thị
        const checkedCheckboxes = $('.attribute-checkbox:checked').filter(function() {
            return $(this).closest('.attribute-value-item').css('display') !== 'none';
        });

        // 1. Gom nhóm các ValueID đã chọn theo AttributeID (Giống create)
        checkedCheckboxes.each(function() {
            const attrId = $(this).data('attribute-id');
            const valueId = $(this).val();
            if (!selectedValues[attrId]) {
                selectedValues[attrId] = [];
            }
            selectedValues[attrId].push({
                id: valueId,
                name: $(this).data('attribute-value-name')
            });
        });
        
        const attrIds = Object.keys(selectedValues);
        const $matrixArea = $('#variant-matrix-area');

        if (attrIds.length === 0) {
            $matrixArea.html('<p class="text-danger">Vui lòng chọn ít nhất một giá trị thuộc tính để tạo biến thể.</p>');
            return;
        }

        // 2. Tạo Ma trận Decartes (Tổ hợp chéo) (Giống create)
        const initialMatrix = [[]];
        const finalMatrix = attrIds.reduce((acc, currentAttrId) => {
            const currentValues = selectedValues[currentAttrId];
            const newAcc = [];
            acc.forEach(variant => {
                currentValues.forEach(value => {
                    newAcc.push(variant.concat(value));
                });
            });
            return newAcc;
        }, initialMatrix);
        
        // 3. Render HTML và điền sẵn dữ liệu cũ
        let html = '<table class="table table-bordered">';
        html += '<thead><tr><th>Tổ hợp</th><th>SKU (*)</th><th>Giá bán (*)</th><th>Tồn kho (*)</th></tr></thead><tbody>';
        
        finalMatrix.forEach((variantCombo, index) => {
            let comboName = '';
            let valueIdsInput = '';
            // Tạo khóa tra cứu (Ví dụ: 13_11)
            const lookupKey = variantCombo.map(item => item.id).sort().join('_'); 
            
            variantCombo.forEach(item => {
                comboName += item.name + ' / ';
                valueIdsInput += `<input type="hidden" name="variants[${index}][attribute_values][]" value="${item.id}">`;
            });
            comboName = comboName.slice(0, -3);

            // Kiểm tra xem tổ hợp này có tồn tại trong dữ liệu cũ không
            const existingVariant = CURRENT_VARIANTS[lookupKey];
            
            const skuValue = existingVariant ? existingVariant.sku : '';
            const priceValue = existingVariant ? existingVariant.price : '';
            const stockValue = existingVariant ? existingVariant.stock : '';
            
            // THAY ĐỔI: Thêm input ẩn để xác định đây là Variants mới hay cũ (Quan trọng cho Controller@update)
            const idInput = existingVariant 
                ? `<input type="hidden" name="variants[${index}][id]" value="${existingVariant.variantID}">`
                : `<input type="hidden" name="variants[${index}][id]" value="NEW">`; // Đánh dấu là NEW

            html += `
                <tr>
                    <td>
                        <strong>${comboName}</strong>
                        ${valueIdsInput}
                        ${idInput}
                    </td>
                    <td>
                        <input type="text" name="variants[${index}][sku]" class="form-control" placeholder="Ví dụ: AX100ZZ-4UG5" value="${skuValue}" required>
                    </td>
                    <td>
                        <input type="number" name="variants[${index}][price]" class="form-control" placeholder="0" min="1000" value="${priceValue}" required>
                    </td>
                    <td>
                        <input type="number" name="variants[${index}][stock]" class="form-control" placeholder="0" min="0" value="${stockValue}" required>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        
        $matrixArea.html(html);
    }
</script>
@stop