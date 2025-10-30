@extends('adminlte::page')

@section('title', 'Thêm Sản phẩm Mới')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-plus-circle"></i> Thêm Sản phẩm Mới
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Sản phẩm</a></li>
                <li class="breadcrumb-item active">Thêm mới</li>
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="fas fa-ban"></i> Có lỗi xảy ra!</h5>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.store') }}" 
          method="POST" 
          enctype="multipart/form-data" 
          id="productForm">
        @csrf
        
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
                                   value="{{ old('productName') }}" 
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
                                      placeholder="Nhập mô tả chi tiết về sản phẩm...">{{ old('description') }}</textarea>
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
                                                    {{ old('categoryID') == $category->categoryID ? 'selected' : '' }}>
                                                {{ $category->categoryName }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('categoryID')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> Chọn danh mục sẽ tự động load thuộc tính phù hợp
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
                                                    {{ old('brandID') == $brand->brandID ? 'selected' : '' }}>
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
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Hướng dẫn:</strong> 
                            <ol class="mb-0 mt-2 pl-3">
                                <li>Chọn danh mục sản phẩm ở trên</li>
                                <li>Hệ thống sẽ tự động hiển thị các thuộc tính phù hợp</li>
                                <li>Chọn giá trị cho mỗi thuộc tính</li>
                                <li>Ma trận biến thể sẽ được tạo tự động</li>
                            </ol>
                        </div>
                        
                        {{-- Phần chọn Thuộc tính động --}}
                        <div class="form-group" id="attribute-selection-area">
                            <label class="d-block">
                                <i class="fas fa-tags"></i> Chọn thuộc tính áp dụng:
                            </label>
                            
                            <div id="attributes-loading" class="text-center my-4" style="display: none;">
                                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                                <p class="mt-2">Đang tải thuộc tính...</p>
                            </div>

                            <div id="attributes-container">
                                <p class="text-muted">
                                    <i class="fas fa-arrow-up"></i> Vui lòng chọn danh mục sản phẩm ở trên
                                </p>
                            </div>

                            <small class="form-text text-danger">
                                <i class="fas fa-info-circle"></i> 
                                Chọn/bỏ chọn thuộc tính sẽ tự động cập nhật ma trận biến thể
                            </small>
                        </div>

                        <hr class="my-4">
                        
                        {{-- Ma trận Biến thể --}}
                        <div id="variant-matrix-wrapper">
                            <h5 class="mb-3" id="variant-matrix-title" style="display: none;">
                                <i class="fas fa-table"></i> Ma trận Biến thể
                                <span class="badge badge-info" id="variant-count">0</span>
                            </h5>
                            
                            <div id="variant-matrix-area">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Chọn thuộc tính để tạo ma trận biến thể
                                </div>
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
                        <div class="form-group">
                            <label for="main_image">
                                <i class="fas fa-image"></i> Ảnh Chính <span class="text-danger">*</span>
                            </label>
                            <div class="custom-file">
                                <input type="file" 
                                       name="images[main]" 
                                       class="custom-file-input @error('images.main') is-invalid @enderror" 
                                       id="main_image"
                                       accept="image/*"
                                       required>
                                <label class="custom-file-label" for="main_image">Chọn file...</label>
                            </div>
                            @error('images.main')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                Định dạng: JPG, PNG, GIF. Tối đa 2MB
                            </small>
                            
                            {{-- Preview --}}
                            <div id="main-image-preview" class="mt-3" style="display: none;">
                                <img src="" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label for="gallery_images">
                                <i class="fas fa-images"></i> Ảnh Thư viện
                            </label>
                            <div class="custom-file">
                                <input type="file" 
                                       name="images[gallery][]" 
                                       class="custom-file-input @error('images.gallery') is-invalid @enderror" 
                                       id="gallery_images"
                                       accept="image/*"
                                       multiple>
                                <label class="custom-file-label" for="gallery_images">Chọn files...</label>
                            </div>
                            @error('images.gallery')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                Tối đa 4 ảnh. Mỗi ảnh không quá 2MB
                            </small>
                            
                            {{-- Preview Gallery --}}
                            <div id="gallery-preview" class="mt-3 row" style="display: none;"></div>
                        </div>
                    </div>
                </div>

                {{-- Card Tips --}}
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-lightbulb"></i> Gợi ý
                        </h3>
                    </div>
                    <div class="card-body">
                        <ul class="pl-3 mb-0">
                            <li>Tên sản phẩm nên ngắn gọn, súc tích</li>
                            <li>Mô tả chi tiết giúp khách hàng hiểu rõ hơn</li>
                            <li>Ảnh chính nên rõ nét, nền trắng</li>
                            <li>SKU nên có format: BRAND-MODEL-VARIANT</li>
                            <li>Giá bán phải lớn hơn 1.000đ</li>
                        </ul>
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
                            <i class="fas fa-save"></i> Lưu Sản phẩm
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
        border-left: 4px solid #007bff;
    }
    
    .attribute-group strong {
        display: block;
        margin-bottom: 10px;
        color: #495057;
        font-size: 1.1em;
    }
    
    .form-check {
        margin-bottom: 10px;
    }
    
    .form-check-label {
        cursor: pointer;
        padding-top: 2px;
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
    
    #gallery-preview .preview-item {
        padding: 5px;
    }
    
    #gallery-preview img {
        max-height: 100px;
        border-radius: 5px;
    }
</style>
@stop

@section('js')
<script>
// ============================================================================
// CONSTANTS
// ============================================================================
const ROUTES = {
    getAttributes: '/admin/products/category/:id/attributes'
};

console.log('🎯 Routes configured:', ROUTES);

// ============================================================================
// KHỞI TẠO
// ============================================================================
$(document).ready(function() {
    console.log('✅ Document ready!');
    
    // Lắng nghe thay đổi category
    $('#categoryID').on('change', function() {
        const categoryID = $(this).val();
        console.log('📁 Category changed to:', categoryID);
        
        if (categoryID) {
            loadAttributesForCategory(categoryID);
        } else {
            $('#attributes-container').html(`
                <p class="text-muted">
                    <i class="fas fa-arrow-up"></i> Vui lòng chọn danh mục sản phẩm
                </p>
            `);
            $('#variant-matrix-area').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Chọn thuộc tính để tạo ma trận biến thể
                </div>
            `);
            $('#variant-matrix-title').hide();
        }
    });
    
    // Preview ảnh chính
    $('#main_image').on('change', function() {
        const file = this.files[0];
        const fileName = file ? file.name : 'Chọn file...';
        $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#main-image-preview').show().find('img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Preview ảnh gallery
    $('#gallery_images').on('change', function() {
        const fileCount = this.files.length;
        const label = fileCount > 1 ? `${fileCount} files đã chọn` : (this.files[0] ? this.files[0].name : 'Chọn files...');
        $(this).siblings('.custom-file-label').addClass('selected').html(label);
        
        const $preview = $('#gallery-preview');
        $preview.empty();
        
        if (fileCount > 0) {
            $preview.show();
            Array.from(this.files).forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $preview.append(`
                        <div class="col-3 preview-item">
                            <img src="${e.target.result}" class="img-thumbnail">
                        </div>
                    `);
                };
                reader.readAsDataURL(file);
            });
        }
    });
    
    // Khởi tạo nếu có old input
    const oldCategoryID = $('#categoryID').val();
    if (oldCategoryID) {
        loadAttributesForCategory(oldCategoryID);
    }
});

// ============================================================================
// LOAD ATTRIBUTES THEO CATEGORY
// ============================================================================
function loadAttributesForCategory(categoryID) {
    console.log('🔄 Loading attributes for category:', categoryID);
    
    $('#attributes-loading').show();
    $('#attributes-container').hide();
    
    $.ajax({
        url: ROUTES.getAttributes.replace(':id', categoryID),
        method: 'GET',
        success: function(attributes) {
            console.log('✅ Attributes loaded:', attributes);
            renderAttributes(attributes);
            $('#attributes-loading').hide();
            $('#attributes-container').show();
            
            // Auto generate variants nếu có old input
            generateVariants();
        },
        error: function(xhr, status, error) {
            console.error('❌ Error loading attributes:', error);
            $('#attributes-loading').hide();
            $('#attributes-container').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Không thể tải thuộc tính: ${error}
                </div>
            `).show();
        }
    });
}

// ============================================================================
// RENDER ATTRIBUTES
// ============================================================================
function renderAttributes(attributes) {
    const $container = $('#attributes-container');
    $container.empty();
    
    if (attributes.length === 0) {
        $container.html(`
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Danh mục này chưa có thuộc tính nào được gán. 
                Vui lòng vào trang quản lý thuộc tính để gán.
            </div>
        `);
        return;
    }
    
    attributes.forEach(attr => {
        let html = `
            <div class="attribute-group" data-attribute-id="${attr.attributeID}">
                <strong>
                    <i class="fas fa-tag"></i> ${attr.attributeName}:
                </strong>
                <div class="row">
        `;
        
        attr.values.forEach(value => {
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
// GENERATE VARIANTS MATRIX
// ============================================================================
function generateVariants() {
    console.log('🔄 Generating variants...');
    
    const selectedValues = {};
    
    // Gom nhóm values theo attribute
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
        $('#variant-matrix-area').html(`
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Vui lòng chọn ít nhất một giá trị thuộc tính để tạo biến thể
            </div>
        `);
        $('#variant-matrix-title').hide();
        $('#variant-count').text('0');
        return;
    }
    
    // Tạo tổ hợp Descartes
    const variants = cartesianProduct(attrIds.map(id => selectedValues[id]));
    
    console.log('✅ Variants generated:', variants.length);
    
    renderVariantMatrix(variants);
    
    $('#variant-count').text(variants.length);
    $('#variant-matrix-title').show();
}

// ============================================================================
// CARTESIAN PRODUCT
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
// RENDER VARIANT MATRIX
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
        const comboName = variantCombo.map(v => v.name).join(' / ');
        
        let hiddenInputs = '';
        variantCombo.forEach(v => {
            hiddenInputs += `<input type="hidden" name="variants[${index}][attribute_values][]" value="${v.id}">`;
        });
        
        html += `
        <tr>
            <td>
                <div class="variant-combo">${comboName}</div>
                ${hiddenInputs}
            </td>
            <td>
                <input type="text" 
                       name="variants[${index}][sku]" 
                       class="form-control form-control-sm" 
                       placeholder="VD: VICTOR-K9900-L" 
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
</script>
@stop