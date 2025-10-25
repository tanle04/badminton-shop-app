@extends('adminlte::page')

@section('title', 'Thêm Sản phẩm Mới')

@section('content_header')
    <h1>Thêm Sản phẩm Mới</h1>
@stop

@section('content')
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row">
            {{-- Cột 1: Thông tin cơ bản & Variants --}}
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header"><h3 class="card-title">Thông tin cơ bản</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="productName">Tên sản phẩm (*)</label>
                            <input type="text" name="productName" class="form-control" value="{{ old('productName') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Mô tả sản phẩm</label>
                            <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="categoryID">Danh mục (*)</label>
                            {{-- THAY ĐỔI: Thêm ID để JS bắt sự kiện --}}
                            <select name="categoryID" id="categoryID" class="form-control" required>
                                <option value="">--- Chọn Danh mục ---</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->categoryID }}" {{ old('categoryID') == $category->categoryID ? 'selected' : '' }}>{{ $category->categoryName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="brandID">Thương hiệu (*)</label>
                            <select name="brandID" class="form-control" required>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->brandID }}" {{ old('brandID') == $brand->brandID ? 'selected' : '' }}>{{ $brand->brandName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Card quản lý Biến thể (Variants) --}}
                <div class="card card-info">
                    <div class="card-header"><h3 class="card-title">Quản lý Biến thể (Variants)</h3></div>
                    <div class="card-body">
                        
                        {{-- Phần chọn Thuộc tính (Attributes) --}}
                        <div class="form-group" id="attribute-selection-area">
                            <label>Chọn các Thuộc tính áp dụng:</label>
                            @foreach ($attributes as $attribute)
                                <div class="attribute-group" 
                                     data-attribute-id="{{ $attribute->attributeID }}" 
                                     data-attribute-name="{{ $attribute->attributeName }}"
                                     id="attribute-{{ $attribute->attributeID }}" style="display: none;"> {{-- Mặc định ẩn --}}
                                    
                                    <strong>{{ $attribute->attributeName }}</strong>:
                                    @foreach ($attribute->values as $value)
                                        <div class="form-check form-check-inline attribute-value-item" 
                                             data-value-id="{{ $value->valueID }}">
                                            
                                            <input class="form-check-input attribute-checkbox" type="checkbox" 
                                                   name="attribute_values_temp[]" {{-- Dùng tên tạm thời --}}
                                                   data-attribute-id="{{ $attribute->attributeID }}"
                                                   data-attribute-value-name="{{ $value->valueName }}"
                                                   value="{{ $value->valueID }}" 
                                                   onchange="generateVariants()">
                                            <label class="form-check-label">{{ $value->valueName }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <hr class="attribute-hr" style="display: none;"> {{-- Mặc định ẩn --}}
                            @endforeach
                        </div>

                        {{-- Vùng hiển thị Ma trận Biến thể Động --}}
                        <h5 id="variant-matrix-title" style="display: none;">Ma trận Biến thể được tạo:</h5>
                        <div id="variant-matrix-area">
                            <p class="text-info">Vui lòng chọn Danh mục sản phẩm và các Thuộc tính áp dụng.</p>
                        </div>
                        
                    </div>
                </div>
            </div>

            {{-- Cột 2: Hình ảnh --}}
            <div class="col-md-4">
                <div class="card card-success">
                    <div class="card-header"><h3 class="card-title">Hình ảnh Sản phẩm</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="main_image">Ảnh Chính (Main Image) (*)</label>
                            <input type="file" name="images[main]" class="form-control-file" required>
                        </div>
                        <div class="form-group">
                            <label for="gallery_images">Ảnh Thư viện (Gallery Images)</label>
                            <input type="file" name="images[gallery][]" class="form-control-file" multiple>
                        </div>
                        <p class="text-muted">Tối đa 4 ảnh thư viện.</p>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-success">Lưu Sản phẩm</button>
        <a href="{{ route('admin.products.index') }}" class="btn btn-default">Hủy</a>
    </form>
@stop

@section('js')
<script>
    // Ánh xạ CategoryID -> Attribute IDs và Value IDs cần hiển thị
    // [attributeID]: [valueID, valueID, ...]
    const CATEGORY_ATTRIBUTES = {
        // ID 1: Vợt cầu lông
        1: { 
            show: [2, 3], // Grip, Trọng lượng
            filter: {}
        }, 
        // ID 2: Giày cầu lông
        2: { 
            show: [1], // Size
            filter: { 1: [1, 2, 3, 4, 5, 6, 7] } // Size EU 36-42 (DB valueID 1 đến 7)
        }, 
        // ID 3: Quần áo cầu lông
        3: { 
            show: [1], // Size
            filter: { 1: [8, 9, 10] } // Size M, L, XL (DB valueID 8, 9, 10)
        } 
        // ID 4: Phụ kiện (Không có variants)
    };
    
    $(document).ready(function() {
        // Lắng nghe sự kiện thay đổi Category
        $('#categoryID').on('change', function() {
            filterAttributes($(this).val());
            generateVariants(); // Reset variants khi đổi Category
        });

        // Khởi tạo trạng thái ban đầu khi tải trang (ví dụ, nếu có old input)
        filterAttributes($('#categoryID').val());
        generateVariants();
    });
    
    // Hàm lọc các thuộc tính và giá trị theo Category
    function filterAttributes(categoryId) {
        categoryId = parseInt(categoryId);
        const config = CATEGORY_ATTRIBUTES[categoryId];
        
        // 1. Ẩn toàn bộ nhóm thuộc tính và HR
        $('.attribute-group').hide();
        $('.attribute-hr').hide();
        $('.attribute-checkbox').prop('checked', false);
        
        // Nếu không có config (VD: Phụ kiện), thoát
        if (!config || config.show.length === 0) return;
        
        // 2. Hiện các nhóm thuộc tính cần thiết và lọc giá trị
        config.show.forEach(attrId => {
            const $group = $('#attribute-' + attrId);
            $group.show();
            $group.next('.attribute-hr').show();
            
            // Lọc giá trị (value) cho thuộc tính này nếu có filter
            if (config.filter && config.filter[attrId]) {
                const allowedValueIds = config.filter[attrId];
                
                $group.find('.attribute-value-item').each(function() {
                    const valueId = parseInt($(this).data('value-id'));
                    if (allowedValueIds.includes(valueId)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                        $(this).find('.attribute-checkbox').prop('checked', false);
                    }
                });
            } else {
                 // Nếu không có filter cụ thể, hiện tất cả value trong group đó
                 $group.find('.attribute-value-item').show();
            }
        });
    }

    // Hàm tạo Ma trận Biến thể (Variant Matrix)
    function generateVariants() {
        const selectedValues = {}; 
        const checkedCheckboxes = $('.attribute-checkbox:checked');
        
        // 1. Gom nhóm các ValueID đã chọn theo AttributeID
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

        // Nếu không có thuộc tính nào được chọn, hiển thị thông báo
        if (attrIds.length === 0) {
             $matrixArea.html('<p class="text-danger">Vui lòng chọn ít nhất một giá trị thuộc tính để tạo biến thể.</p>');
             $('#variant-matrix-title').hide();
             return;
        }
        
        // 2. Tạo Ma trận Decartes (Tổ hợp chéo)
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
        
        // 3. Render HTML cho Ma trận Biến thể
        let html = '<table class="table table-bordered">';
        html += '<thead><tr><th>Tổ hợp</th><th>SKU (*)</th><th>Giá bán (*)</th><th>Tồn kho (*)</th></tr></thead><tbody>';
        
        finalMatrix.forEach((variantCombo, index) => {
            let comboName = '';
            let valueIdsInput = '';
            
            variantCombo.forEach(item => {
                comboName += item.name + ' / ';
                // THAY ĐỔI: Gửi valueID của từng variant theo cấu trúc mảng
                valueIdsInput += `<input type="hidden" name="variants[${index}][attribute_values][]" value="${item.id}">`;
            });
            comboName = comboName.slice(0, -3); // Xóa dấu '/' cuối cùng
            
            html += `
                <tr>
                    <td>
                        <strong>${comboName}</strong>
                        ${valueIdsInput}
                    </td>
                    <td>
                        <input type="text" name="variants[${index}][sku]" class="form-control" placeholder="Ví dụ: AX100ZZ-4UG5" required>
                    </td>
                    <td>
                        <input type="number" name="variants[${index}][price]" class="form-control" placeholder="0" min="1000" required>
                    </td>
                    <td>
                        <input type="number" name="variants[${index}][stock]" class="form-control" placeholder="0" min="0" required>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table>';
        
        $matrixArea.html(html);
        $('#variant-matrix-title').show();
    }
</script>
@stop