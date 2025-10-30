@extends('adminlte::page')

@section('title', 'Chỉnh sửa Sản phẩm: ' . $product->productName)

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-edit"></i> Chỉnh sửa Sản phẩm
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Sản phẩm</a></li>
                <li class="breadcrumb-item active">Chỉnh sửa</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" id="productForm">
        @csrf
        @method('PUT')
        
        <div class="row">
            {{-- CỘT TRÁI: Thông tin cơ bản và Biến thể --}}
            <div class="col-lg-8">
                {{-- Card 1: Thông tin cơ bản --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Thông tin cơ bản
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="productName">
                                Tên sản phẩm <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="productName" 
                                   id="productName"
                                   class="form-control @error('productName') is-invalid @enderror" 
                                   value="{{ old('productName', $product->productName) }}" 
                                   required
                                   placeholder="Ví dụ: Vợt cầu lông Victor Thruster K 9900">
                            @error('productName')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Mô tả sản phẩm</label>
                            <textarea name="description" 
                                      id="description"
                                      class="form-control @error('description') is-invalid @enderror" 
                                      rows="4"
                                      placeholder="Nhập mô tả chi tiết về sản phẩm...">{{ old('description', $product->description) }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="categoryID">
                                        Danh mục <span class="text-danger">*</span>
                                    </label>
                                    <select name="categoryID" 
                                            id="categoryID" 
                                            class="form-control @error('categoryID') is-invalid @enderror" 
                                            required>
                                        <option value="">-- Chọn danh mục --</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->categoryID }}" 
                                                    {{ $product->categoryID == $category->categoryID ? 'selected' : '' }}>
                                                {{ $category->categoryName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('categoryID')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Thay đổi danh mục sẽ cập nhật danh sách thuộc tính
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="brandID">
                                        Thương hiệu <span class="text-danger">*</span>
                                    </label>
                                    <select name="brandID" 
                                            id="brandID"
                                            class="form-control @error('brandID') is-invalid @enderror" 
                                            required>
                                        <option value="">-- Chọn thương hiệu --</option>
                                        @foreach ($brands as $brand)
                                            <option value="{{ $brand->brandID }}" 
                                                    {{ $product->brandID == $brand->brandID ? 'selected' : '' }}>
                                                {{ $brand->brandName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('brandID')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Quản lý Biến thể --}}
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-th"></i> Quản lý Biến thể (Variants)
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lưu ý quan trọng:</strong> 
                            Hệ thống sẽ tự động so sánh và cập nhật biến thể dựa trên tổ hợp thuộc tính được chọn.
                            <ul class="mb-0 mt-2">
                                <li>Biến thể cũ khớp với tổ hợp mới sẽ được giữ nguyên</li>
                                <li>Biến thể mới sẽ được tạo tự động</li>
                                <li>Biến thể không còn trong danh sách sẽ bị xóa</li>
                            </ul>
                        </div>
                        
                        {{-- Phần chọn Thuộc tính động --}}
                        <div class="form-group" id="attribute-selection-area">
                            <label class="d-block">
                                <i class="fas fa-tags"></i> Chọn thuộc tính áp dụng:
                            </label>
                            
                            <div id="attributes-loading" class="text-center my-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Đang tải thuộc tính...</p>
                            </div>

                            <div id="attributes-container" style="display: none;">
                                {{-- Attributes sẽ được load động bằng JavaScript --}}
                            </div>

                            <small class="form-text text-danger">
                                <i class="fas fa-info-circle"></i> 
                                Chọn/bỏ chọn thuộc tính sẽ tự động cập nhật ma trận biến thể bên dưới
                            </small>
                        </div>

                        <hr class="my-4">
                        
                        {{-- Ma trận Biến thể --}}
                        <div id="variant-matrix-wrapper">
                            <h5 class="mb-3">
                                <i class="fas fa-table"></i> Ma trận Biến thể
                                <span class="badge badge-info" id="variant-count">0</span>
                            </h5>
                            
                            <div id="variant-matrix-loading" class="text-center my-4">
                                <i class="fas fa-spinner fa-spin fa-2x text-info"></i>
                                <p class="mt-2">Đang tạo ma trận biến thể...</p>
                            </div>

                            <div id="variant-matrix-area" style="display: none;">
                                {{-- Ma trận sẽ được tạo bằng JavaScript --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CỘT PHẢI: Hình ảnh --}}
            <div class="col-lg-4">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-images"></i> Hình ảnh Sản phẩm
                        </h3>
                    </div>
                    <div class="card-body">
                        {{-- Ảnh hiện tại --}}
                        <div class="mb-4">
                            <label class="d-block">
                                <i class="fas fa-image"></i> Ảnh hiện tại:
                            </label>
                            <div class="row" id="current-images-row">
                                @forelse ($product->images as $image)
                                    <div class="col-6 mb-3 image-item-{{ $image->imageID }}">
                                        <div class="position-relative">
                                            <img src="{{ asset('storage/' . $image->imageUrl) }}" 
                                                 class="img-thumbnail" 
                                                 style="width: 100%; height: 120px; object-fit: cover;">
                                            
                                            <span class="badge badge-{{ $image->imageType == 'main' ? 'primary' : 'secondary' }} position-absolute" 
                                                  style="top: 5px; left: 5px;">
                                                {{ $image->imageType == 'main' ? 'CHÍNH' : 'Gallery' }}
                                            </span>
                                            
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm position-absolute btn-remove-image" 
                                                    style="top: 5px; right: 5px;"
                                                    data-image-id="{{ $image->imageID }}"
                                                    title="Xóa ảnh">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted text-center">Chưa có ảnh nào</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <hr>

                        {{-- Upload ảnh mới --}}
                        <div class="form-group">
                            <label for="new_main_image">
                                <i class="fas fa-upload"></i> Cập nhật Ảnh Chính
                            </label>
                            <div class="custom-file">
                                <input type="file" 
                                       name="images[main]" 
                                       class="custom-file-input" 
                                       id="new_main_image"
                                       accept="image/*">
                                <label class="custom-file-label" for="new_main_image">Chọn file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Chọn file mới để thay thế ảnh Chính hiện tại
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="new_gallery_images">
                                <i class="fas fa-images"></i> Thêm Ảnh Thư viện
                            </label>
                            <div class="custom-file">
                                <input type="file" 
                                       name="images[gallery][]" 
                                       class="custom-file-input" 
                                       id="new_gallery_images"
                                       accept="image/*"
                                       multiple>
                                <label class="custom-file-label" for="new_gallery_images">Chọn files...</label>
                            </div>
                            <small class="form-text text-muted">
                                Các ảnh mới sẽ được thêm vào thư viện hiện tại
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Card Thống kê --}}
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i> Thống kê
                        </h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Tổng biến thể:</dt>
                            <dd class="col-sm-6">
                                <span class="badge badge-primary">{{ $product->variants->count() }}</span>
                            </dd>

                            <dt class="col-sm-6">Tổng tồn kho:</dt>
                            <dd class="col-sm-6">
                                <span class="badge badge-success">{{ $product->stock }}</span>
                            </dd>

                            <dt class="col-sm-6">Giá từ:</dt>
                            <dd class="col-sm-6">
                                <span class="badge badge-info">{{ number_format($product->price) }}đ</span>
                            </dd>

                            <dt class="col-sm-6">Trạng thái:</dt>
                            <dd class="col-sm-6">
                                <span class="badge badge-{{ $product->is_active ? 'success' : 'danger' }}">
                                    {{ $product->is_active ? 'Hoạt động' : 'Vô hiệu' }}
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Cập nhật Sản phẩm
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('css')
<style>
    .card-outline {
        border-top: 3px solid;
    }
    
    .attribute-group {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        margin-bottom: 15px;
    }
    
    .attribute-group strong {
        display: block;
        margin-bottom: 10px;
        color: #495057;
        font-size: 1.1em;
    }
    
    .form-check-inline {
        margin-right: 15px;
        margin-bottom: 10px;
    }
    
    .variant-table th {
        background: #f4f6f9;
        font-weight: 600;
    }
    
    .variant-table td {
        vertical-align: middle;
    }
    
    .variant-combo {
        font-weight: 600;
        color: #007bff;
    }
    
    #variant-count {
        font-size: 0.9em;
    }
    
    .custom-file-label::after {
        content: "Chọn";
    }
</style>
@stop

@section('js')
{{-- Toastr --}}
<link rel="stylesheet" href="{{ asset('vendor/toastr/toastr.min.css') }}">
<script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>

<script>
// ============================================================================
// CONSTANTS VÀ BIẾN TOÀN CỤC
// ============================================================================
const PRODUCT_ID = {{ $product->productID }};
const CURRENT_CATEGORY_ID = {{ $product->categoryID }};

// Dữ liệu variants hiện tại (key = tổ hợp valueIDs)
const CURRENT_VARIANTS = {!! json_encode($product->variants->mapWithKeys(function($item) {
    $key = collect($item->attributeValues)->pluck('valueID')->sort()->join('_');
    return [$key => [
        'variantID' => $item->variantID,
        'sku' => $item->sku,
        'price' => $item->price,
        'stock' => $item->stock,
        'attribute_values' => collect($item->attributeValues)->pluck('valueID')->toArray()
    ]];
})) !!};

// Category attributes mapping từ server
const CATEGORY_ATTRIBUTES_MAP = @json($categoryAttributes);

console.log('🎯 Product ID:', PRODUCT_ID);
console.log('📦 Current Variants:', CURRENT_VARIANTS);
console.log('🗂️ Category Mapping:', CATEGORY_ATTRIBUTES_MAP);

// ============================================================================
// KHỞI TẠO KHI DOM READY
// ============================================================================
$(document).ready(function() {
    console.log('✅ Document ready!');
    
    // Load thuộc tính của category hiện tại
    loadAttributesForCategory(CURRENT_CATEGORY_ID);
    
    // Lắng nghe thay đổi category
    $('#categoryID').on('change', function() {
        const categoryID = $(this).val();
        console.log('📁 Category changed to:', categoryID);
        
        if (categoryID) {
            loadAttributesForCategory(categoryID);
        } else {
            $('#attributes-container').html('<p class="text-warning">Vui lòng chọn danh mục</p>');
            $('#variant-matrix-area').html('<p class="text-warning">Chưa có thuộc tính nào được chọn</p>');
        }
    });
    
    // Xử lý xóa ảnh
    $('.btn-remove-image').on('click', function() {
        const imageId = $(this).data('image-id');
        
        if (confirm('Bạn có chắc chắn muốn xóa hình ảnh này?')) {
            deleteImage(imageId);
        }
    });
    
    // Xử lý preview file upload
    $('#new_main_image').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
    });
    
    $('#new_gallery_images').on('change', function() {
        const fileCount = this.files.length;
        const label = fileCount > 1 ? `${fileCount} files đã chọn` : $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass('selected').html(label);
    });
});

// ============================================================================
// HÀM LOAD THUỘC TÍNH THEO CATEGORY
// ============================================================================
function loadAttributesForCategory(categoryID) {
    console.log('🔄 Loading attributes for category:', categoryID);
    
    $('#attributes-loading').show();
    $('#attributes-container').hide();
    $('#variant-matrix-loading').show();
    $('#variant-matrix-area').hide();
    
    $.ajax({
        url: `/admin/products/category/${categoryID}/attributes`,
        method: 'GET',
        success: function(attributes) {
            console.log('✅ Attributes loaded:', attributes);
            renderAttributes(attributes);
            $('#attributes-loading').hide();
            $('#attributes-container').show();
            
            // Tự động generate variants sau khi load xong
            generateVariants();
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading attributes:', error);
            toastr.error('Không thể tải thuộc tính: ' + error);
            $('#attributes-loading').hide();
            $('#attributes-container').html('<p class="text-danger">Lỗi khi tải thuộc tính</p>').show();
        }
    });
}

// ============================================================================
// HÀM RENDER THUỘC TÍNH
// ============================================================================
function renderAttributes(attributes) {
    const $container = $('#attributes-container');
    $container.empty();
    
    if (attributes.length === 0) {
        $container.html('<div class="alert alert-info"><i class="fas fa-info-circle"></i> Danh mục này chưa có thuộc tính nào được gán</div>');
        return;
    }
    
    // Lấy tất cả valueIDs đang được dùng trong variants hiện tại
    const currentValueIDs = Object.values(CURRENT_VARIANTS)
        .flatMap(v => v.attribute_values);
    
    console.log('📋 Current value IDs:', currentValueIDs);
    
    attributes.forEach(attr => {
        let html = `
            <div class="attribute-group" data-attribute-id="${attr.attributeID}">
                <strong>
                    <i class="fas fa-tag"></i> ${attr.attributeName}:
                </strong>
                <div class="row">
        `;
        
        attr.values.forEach(value => {
            const isChecked = currentValueIDs.includes(value.valueID) ? 'checked' : '';
            
            html += `
                <div class="col-md-4 col-sm-6">
                    <div class="form-check">
                        <input class="form-check-input attribute-checkbox" 
                               type="checkbox" 
                               name="attribute_values_temp[]"
                               data-attribute-id="${attr.attributeID}"
                               data-attribute-name="${attr.attributeName}"
                               data-value-id="${value.valueID}"
                               data-value-name="${value.valueName}"
                               value="${value.valueID}"
                               ${isChecked}
                               onchange="generateVariants()">
                        <label class="form-check-label">
                            ${value.valueName}
                        </label>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
        
        $container.append(html);
    });
    
    console.log('✅ Attributes rendered');
}

// ============================================================================
// HÀM TẠO MA TRẬN BIẾN THỂ
// ============================================================================
function generateVariants() {
    console.log('🔄 Generating variants...');
    
    const selectedValues = {};
    
    // Gom nhóm các giá trị đã chọn theo attribute
    $('.attribute-checkbox:checked').each(function() {
        const attrId = $(this).data('attribute-id');
        const valueId = $(this).val();
        const valueName = $(this).data('value-name');
        
        if (!selectedValues[attrId]) {
            selectedValues[attrId] = [];
        }
        
        selectedValues[attrId].push({
            id: valueId,
            name: valueName
        });
    });
    
    console.log('📊 Selected values:', selectedValues);
    
    const attrIds = Object.keys(selectedValues);
    
    if (attrIds.length === 0) {
        $('#variant-matrix-area').html('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Vui lòng chọn ít nhất một giá trị thuộc tính</div>').show();
        $('#variant-count').text('0');
        $('#variant-matrix-loading').hide();
        return;
    }
    
    // Tạo tổ hợp Descartes
    const variants = cartesianProduct(attrIds.map(id => selectedValues[id]));
    
    console.log('✅ Variants generated:', variants.length);
    
    renderVariantMatrix(variants);
    
    $('#variant-count').text(variants.length);
    $('#variant-matrix-loading').hide();
    $('#variant-matrix-area').show();
}

// ============================================================================
// HÀM TẠO TÍCH DESCARTES (CARTESIAN PRODUCT)
// ============================================================================
function cartesianProduct(arrays) {
    return arrays.reduce((acc, curr) => {
        const result = [];
        acc.forEach(a => {
            curr.forEach(b => {
                result.push(a.concat([b]));
            });
        });
        return result;
    }, [[]]);
}

// ============================================================================
// HÀM RENDER MA TRẬN BIẾN THỂ
// ============================================================================
function renderVariantMatrix(variants) {
    let html = `
        <div class="table-responsive">
            <table class="table table-bordered table-hover variant-table">
                <thead class="thead-light">
                    <tr>
                        <th width="30%">
                            <i class="fas fa-cubes"></i> Tổ hợp
                        </th>
                        <th width="25%">
                            <i class="fas fa-barcode"></i> SKU <span class="text-danger">*</span>
                        </th>
                        <th width="20%">
                            <i class="fas fa-dollar-sign"></i> Giá bán <span class="text-danger">*</span>
                        </th>
                        <th width="25%">
                            <i class="fas fa-warehouse"></i> Tồn kho <span class="text-danger">*</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    variants.forEach((variantCombo, index) => {
        // Tạo key để tra cứu variant cũ
        const valueIds = variantCombo.map(v => v.id).sort();
        const lookupKey = valueIds.join('_');
        
        // Tên tổ hợp
        const comboName = variantCombo.map(v => v.name).join(' / ');
        
        // Kiểm tra xem có variant cũ không
        const existingVariant = CURRENT_VARIANTS[lookupKey];
        
        const skuValue = existingVariant ? existingVariant.sku : '';
        const priceValue = existingVariant ? existingVariant.price : '';
        const stockValue = existingVariant ? existingVariant.stock : '';
        
        // Hidden inputs
        let hiddenInputs = '';
        variantCombo.forEach(v => {
            hiddenInputs += `<input type="hidden" name="variants[${index}][attribute_values][]" value="${v.id}">`;
        });
        
        // ID input (NEW hoặc variantID)
        hiddenInputs += existingVariant 
            ? `<input type="hidden" name="variants[${index}][id]" value="${existingVariant.variantID}">`
            : `<input type="hidden" name="variants[${index}][id]" value="NEW">`;
        
        const statusBadge = existingVariant 
            ? '<span class="badge badge-success">Đã tồn tại</span>'
            : '<span class="badge badge-primary">Mới</span>';
        
        html += `
            <tr>
                <td>
                    <div class="variant-combo">${comboName}</div>
                    <small>${statusBadge}</small>
                    ${hiddenInputs}
                </td>
                <td>
                    <input type="text" 
                           name="variants[${index}][sku]" 
                           class="form-control form-control-sm" 
                           placeholder="Ví dụ: VICTOR-K9900-L" 
                           value="${skuValue}" 
                           required>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" 
                               name="variants[${index}][price]" 
                               class="form-control" 
                               placeholder="0" 
                               min="1000" 
                               step="1000"
                               value="${priceValue}" 
                               required>
                        <div class="input-group-append">
                            <span class="input-group-text">đ</span>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="number" 
                           name="variants[${index}][stock]" 
                           class="form-control form-control-sm" 
                           placeholder="0" 
                           min="0" 
                           value="${stockValue}" 
                           required>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    $('#variant-matrix-area').html(html);
}

// ============================================================================
// HÀM XÓA ẢNH
// ============================================================================
function deleteImage(imageId) {
    console.log('🗑️ Deleting image:', imageId);
    
    $.ajax({
        url: `/admin/products/${PRODUCT_ID}/images/${imageId}`,
        type: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}',
        },
        success: function(response) {
            console.log('✅ Image deleted:', response);
            if (response.success) {
                $(`.image-item-${imageId}`).fadeOut(300, function() {
                    $(this).remove();
                });
                toastr.success(response.message || 'Ảnh đã được xóa');
            } else {
                toastr.error(response.message || 'Lỗi khi xóa ảnh');
            }
        },
        error: function(xhr) {
            console.error('❌ Error deleting image:', xhr);
            const response = JSON.parse(xhr.responseText);
            toastr.error(response.message || 'Lỗi server khi xóa ảnh');
        }
    });
}
</script>
@stop