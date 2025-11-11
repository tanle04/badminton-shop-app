{{-- resources/views/admin/product_discounts/create.blade.php --}}
@extends('adminlte::page')

@section('title', 'Tạo Chương trình Giảm giá mới')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-plus"></i> Tạo Chương trình Giảm giá mới
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.product-discounts.index') }}">Giảm giá</a></li>
                <li class="breadcrumb-item active">Tạo mới</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    {{-- Alert Messages --}}
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

    <form action="{{ route('admin.product-discounts.store') }}" 
          method="POST" 
          id="discountForm">
        @csrf
        
        <div class="row">
            {{-- Left Column --}}
            <div class="col-lg-8">
                {{-- Card 1: Thông tin cơ bản --}}
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Thông tin cơ bản
                        </h3>
                    </div>
                    <div class="card-body">
                        {{-- Hiển thị thời gian hiện tại --}}
                        <div class="alert alert-info">
                            <i class="fas fa-clock"></i> Thời gian hiện tại (Việt Nam): 
                            <strong>{{ \Carbon\Carbon::now('Asia/Ho_Chi_Minh')->format('d/m/Y H:i:s') }}</strong>
                        </div>
                        
                        {{-- Tên chương trình --}}
                        <div class="form-group">
                            <label for="discountName">
                                Tên Chương trình <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="discountName" 
                                   id="discountName" 
                                   class="form-control @error('discountName') is-invalid @enderror" 
                                   value="{{ old('discountName') }}" 
                                   required
                                   placeholder="Ví dụ: Flash Sale cuối tuần">
                            @error('discountName')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb"></i> Tên ngắn gọn, dễ hiểu để phân biệt với các chương trình khác
                            </small>
                        </div>

                        <hr>

                        {{-- Loại và giá trị giảm --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="discountType">
                                        Loại Giảm Giá <span class="text-danger">*</span>
                                    </label>
                                    <select name="discountType" 
                                            id="discountType" 
                                            class="form-control @error('discountType') is-invalid @enderror" 
                                            required>
                                        <option value="percentage" {{ old('discountType') == 'percentage' ? 'selected' : '' }}>
                                            Phần trăm (%)
                                        </option>
                                        <option value="fixed" {{ old('discountType') == 'fixed' ? 'selected' : '' }}>
                                            Cố định (VNĐ)
                                        </option>
                                    </select>
                                    @error('discountType')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="discountValue">
                                        Giá trị Giảm <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               name="discountValue" 
                                               id="discountValue" 
                                               class="form-control @error('discountValue') is-invalid @enderror" 
                                               value="{{ old('discountValue') }}" 
                                               min="0" 
                                               step="0.01"
                                               required
                                               placeholder="0">
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="value-unit">%</span>
                                        </div>
                                    </div>
                                    @error('discountValue')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    {{-- ⭐ Hiển thị giá min --}}
                                    <small class="form-text text-muted" id="price-hint">
                                        <i class="fas fa-info-circle"></i> Chọn đối tượng để xem giá tham khảo
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4" id="maxDiscountAmountField">
                                <div class="form-group">
                                    <label for="maxDiscountAmount">
                                        Giảm tối đa
                                        <i class="fas fa-info-circle text-muted" 
                                           title="Chỉ áp dụng cho giảm theo %"></i>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" 
                                               name="maxDiscountAmount" 
                                               id="maxDiscountAmount" 
                                               class="form-control @error('maxDiscountAmount') is-invalid @enderror" 
                                               value="{{ old('maxDiscountAmount') }}" 
                                               min="0"
                                               placeholder="Không giới hạn">
                                        <div class="input-group-append">
                                            <span class="input-group-text">đ</span>
                                        </div>
                                    </div>
                                    @error('maxDiscountAmount')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        {{-- Thời gian --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="startDate">
                                        Ngày Bắt đầu <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-calendar-alt"></i>
                                            </span>
                                        </div>
                                        <input type="datetime-local" 
                                               name="startDate" 
                                               id="startDate" 
                                               class="form-control @error('startDate') is-invalid @enderror" 
                                               value="{{ old('startDate') }}" 
                                               required>
                                    </div>
                                    @error('startDate')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-clock"></i> Múi giờ: Việt Nam (UTC+7)
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="endDate">
                                        Ngày Kết thúc <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-calendar-times"></i>
                                            </span>
                                        </div>
                                        <input type="datetime-local" 
                                               name="endDate" 
                                               id="endDate" 
                                               class="form-control @error('endDate') is-invalid @enderror" 
                                               value="{{ old('endDate') }}" 
                                               required>
                                    </div>
                                    @error('endDate')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-clock"></i> Múi giờ: Việt Nam (UTC+7)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Đối tượng áp dụng --}}
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-bullseye"></i> Đối tượng áp dụng
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Lưu ý:</strong> Chọn loại đối tượng trước, sau đó chọn đối tượng cụ thể. 
                            Hệ thống sẽ kiểm tra giá trị giảm giá tự động.
                        </div>

                        {{-- ⭐ BƯỚC 1: Chọn loại --}}
                        <div class="form-group">
                            <label for="appliedToType">
                                Bước 1: Chọn loại đối tượng <span class="text-danger">*</span>
                            </label>
                            <select name="appliedToType" 
                                    id="appliedToType" 
                                    class="form-control @error('appliedToType') is-invalid @enderror" 
                                    required>
                                <option value="">-- Chọn loại đối tượng --</option>
                                <option value="category" {{ old('appliedToType') == 'category' ? 'selected' : '' }}>
                                    <i class="fas fa-folder"></i> Danh mục
                                </option>
                                <option value="brand" {{ old('appliedToType') == 'brand' ? 'selected' : '' }}>
                                    <i class="fas fa-copyright"></i> Thương hiệu
                                </option>
                                <option value="product" {{ old('appliedToType') == 'product' ? 'selected' : '' }}>
                                    <i class="fas fa-box"></i> Sản phẩm
                                </option>
                                <option value="variant" {{ old('appliedToType') == 'variant' ? 'selected' : '' }}>
                                    <i class="fas fa-boxes"></i> Biến thể cụ thể
                                </option>
                            </select>
                            @error('appliedToType')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <hr>

                        {{-- ⭐ BƯỚC 2: Chọn Category/Brand --}}
                        <div id="select-category" class="form-group" style="display: none;">
                            <label for="categorySelect">
                                Bước 2: Chọn Danh mục <span class="text-danger">*</span>
                            </label>
                            <select id="categorySelect" class="form-control">
                                <option value="">-- Chọn danh mục --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->categoryID }}">
                                        {{ $category->categoryName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="select-brand" class="form-group" style="display: none;">
                            <label for="brandSelect">
                                Bước 2: Chọn Thương hiệu <span class="text-danger">*</span>
                            </label>
                            <select id="brandSelect" class="form-control">
                                <option value="">-- Chọn thương hiệu --</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->brandID }}">
                                        {{ $brand->brandName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ⭐ BƯỚC 2/3: Chọn Product --}}
                        <div id="select-product" class="form-group" style="display: none;">
                            <label for="productSelect">
                                <span id="product-label">Bước 2: Chọn Sản phẩm</span> <span class="text-danger">*</span>
                            </label>
                            <select id="productSelect" class="form-control">
                                <option value="">-- Chọn sản phẩm --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->productID }}">
                                        {{ $product->productName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ⭐ BƯỚC 3: Chọn Variant (chỉ hiện khi chọn type = variant) --}}
                        <div id="select-variant" class="form-group" style="display: none;">
                            <label for="variantSelect">
                                Bước 3: Chọn Biến thể cụ thể <span class="text-danger">*</span>
                            </label>
                            <select id="variantSelect" class="form-control">
                                <option value="">-- Chọn sản phẩm trước --</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Hiển thị SKU, giá và tồn kho của từng biến thể
                            </small>
                        </div>

                        {{-- Hidden input để submit --}}
                        <input type="hidden" name="appliedToID" id="appliedToID" value="{{ old('appliedToID') }}">
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-lg-4">
                {{-- Card 3: Trạng thái --}}
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-toggle-on"></i> Trạng thái
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-switch custom-switch-lg">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   id="isActive" 
                                   name="isActive" 
                                   value="1" 
                                   {{ old('isActive', 1) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="isActive">
                                <strong>Kích hoạt chương trình</strong>
                                <br>
                                <small class="text-muted">
                                    Bật để chương trình có hiệu lực ngay lập tức
                                </small>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Thông tin giá --}}
                <div class="card card-warning card-outline" id="price-info-card" style="display: none;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-dollar-sign"></i> Thông tin giá
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="price-info-content">
                            <p class="text-muted">Chọn đối tượng để xem thông tin giá</p>
                        </div>
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
                            <i class="fas fa-save"></i> Tạo Chương trình
                        </button>
                        <a href="{{ route('admin.product-discounts.index') }}" class="btn btn-secondary btn-lg">
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
    .custom-switch-lg .custom-control-label::before {
        height: 2rem;
        width: 3.5rem;
        border-radius: 1rem;
    }
    
    .custom-switch-lg .custom-control-label::after {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
    }
    
    .custom-switch-lg .custom-control-input:checked ~ .custom-control-label::after {
        transform: translateX(1.5rem);
    }
    
    .card-outline {
        border-top: 3px solid;
    }
    
    .input-group-text {
        min-width: 45px;
        justify-content: center;
    }
    
    .price-badge {
        font-size: 1.1rem;
        padding: 0.5rem 1rem;
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    console.log('✅ Form create ready - Timezone: Asia/Ho_Chi_Minh');
    
    const $appliedToType = $('#appliedToType');
    const $categorySelect = $('#select-category');
    const $brandSelect = $('#select-brand');
    const $productSelect = $('#select-product');
    const $variantSelect = $('#select-variant');
    const $appliedToID = $('#appliedToID');
    const $discountType = $('#discountType');
    const $discountValue = $('#discountValue');
    const $maxDiscountField = $('#maxDiscountAmountField');
    const $valueUnit = $('#value-unit');
    const $priceHint = $('#price-hint');
    const $priceInfoCard = $('#price-info-card');
    const $priceInfoContent = $('#price-info-content');

    // ============================================================================
    // 1. Toggle Max Discount Amount field
    // ============================================================================
    function toggleMaxDiscount() {
        const type = $discountType.val();
        
        if (type === 'percentage') {
            $maxDiscountField.show();
            $('#maxDiscountAmount').prop('disabled', false);
            $valueUnit.text('%');
        } else {
            $maxDiscountField.hide();
            $('#maxDiscountAmount').prop('disabled', true).val('');
            $valueUnit.text('đ');
        }
    }
    
    toggleMaxDiscount();
    $discountType.on('change', toggleMaxDiscount);

    // ============================================================================
    // 2. Hiển thị dropdown tương ứng khi chọn loại đối tượng
    // ============================================================================
    $appliedToType.on('change', function() {
        const type = $(this).val();
        
        // Ẩn tất cả
        $categorySelect.hide();
        $brandSelect.hide();
        $productSelect.hide();
        $variantSelect.hide();
        $priceInfoCard.hide();
        
        // Reset values
        $('#categorySelect, #brandSelect, #productSelect, #variantSelect').val('');
        $appliedToID.val('');
        
        // Hiển thị theo loại
        switch(type) {
            case 'category':
                $categorySelect.show();
                break;
            case 'brand':
                $brandSelect.show();
                break;
            case 'product':
                $('#product-label').text('Bước 2: Chọn Sản phẩm (Sale tất cả variants)');
                $productSelect.show();
                break;
            case 'variant':
                $('#product-label').text('Bước 2: Chọn Sản phẩm');
                $productSelect.show();
                $variantSelect.show();
                break;
        }
    });

    // ============================================================================
    // 3. Xử lý khi chọn Category
    // ============================================================================
    $('#categorySelect').on('change', function() {
        const categoryID = $(this).val();
        if (categoryID) {
            $appliedToID.val(categoryID);
            fetchMinPrice('category', categoryID);
        } else {
            $appliedToID.val('');
            $priceInfoCard.hide();
        }
    });

    // ============================================================================
    // 4. Xử lý khi chọn Brand
    // ============================================================================
    $('#brandSelect').on('change', function() {
        const brandID = $(this).val();
        if (brandID) {
            $appliedToID.val(brandID);
            fetchMinPrice('brand', brandID);
        } else {
            $appliedToID.val('');
            $priceInfoCard.hide();
        }
    });

    // ============================================================================
    // 5. Xử lý khi chọn Product
    // ============================================================================
    $('#productSelect').on('change', function() {
        const productID = $(this).val();
        const type = $appliedToType.val();
        
        if (!productID) {
            $appliedToID.val('');
            $('#variantSelect').html('<option value="">-- Chọn sản phẩm trước --</option>');
            $priceInfoCard.hide();
            return;
        }
        
        if (type === 'product') {
            // Nếu chọn product (sale all variants)
            $appliedToID.val(productID);
            fetchMinPrice('product', productID);
        } else if (type === 'variant') {
            // Nếu chọn variant, load danh sách variants
            loadVariants(productID);
        }
    });

    // ============================================================================
    // 6. Load danh sách Variants của Product
    // ============================================================================
    function loadVariants(productID) {
        $('#variantSelect').html('<option value="">Đang tải...</option>');
        
        $.ajax({
url: '{{ route("admin.product-discounts.get-product-variants", ["id" => ":id"]) }}'.replace(':id', productID),            method: 'GET',
            success: function(variants) {
                let options = '<option value="">-- Chọn biến thể --</option>';
                
                if (variants.length === 0) {
                    options = '<option value="">Sản phẩm không có biến thể</option>';
                } else {
                    variants.forEach(v => {
                        const priceFormatted = new Intl.NumberFormat('vi-VN').format(v.price);
                        options += `<option value="${v.variantID}">
                            ${v.sku} - ${priceFormatted}đ (Tồn: ${v.stock})
                        </option>`;
                    });
                }
                
                $('#variantSelect').html(options);
            },
            error: function(xhr) {
                console.error('Lỗi load variants:', xhr);
                $('#variantSelect').html('<option value="">Lỗi tải dữ liệu</option>');
            }
        });
    }

    // ============================================================================
    // 7. Xử lý khi chọn Variant
    // ============================================================================
    $('#variantSelect').on('change', function() {
        const variantID = $(this).val();
        if (variantID) {
            $appliedToID.val(variantID);
            fetchMinPrice('variant', variantID);
        } else {
            $appliedToID.val('');
            $priceInfoCard.hide();
        }
    });

    // ============================================================================
    // 8. Fetch giá thấp nhất và hiển thị
    // ============================================================================
    function fetchMinPrice(type, id) {
        $.ajax({
            url: '{{ route("admin.product-discounts.get-min-price") }}',
            method: 'GET',
            data: { type: type, id: id },
            success: function(response) {
                displayPriceInfo(type, response.minPrice);
            },
            error: function(xhr) {
                console.error('Lỗi fetch price:', xhr);
                $priceInfoCard.hide();
            }
        });
    }

    // ============================================================================
    // 9. Hiển thị thông tin giá
    // ============================================================================
    function displayPriceInfo(type, minPrice) {
        const priceFormatted = new Intl.NumberFormat('vi-VN').format(minPrice);
        
        const typeLabels = {
            'category': 'Danh mục',
            'brand': 'Thương hiệu',
            'product': 'Sản phẩm',
            'variant': 'Biến thể'
        };
        
        let html = `
            <dl class="row mb-0">
                <dt class="col-sm-6">Loại:</dt>
                <dd class="col-sm-6">${typeLabels[type]}</dd>
                
                <dt class="col-sm-6">Giá thấp nhất:</dt>
                <dd class="col-sm-6">
                    <span class="badge badge-success price-badge">
                        ${priceFormatted}đ
                    </span>
                </dd>
            </dl>
            <hr>
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                ${$discountType.val() === 'fixed' 
                    ? 'Giá trị giảm không được vượt quá giá này' 
                    : 'Giá trị % sẽ được tính dựa trên giá này'}
            </small>
        `;
        
        $priceInfoContent.html(html);
        $priceInfoCard.fadeIn();
        
        // Cập nhật hint
        $priceHint.html(`
            <i class="fas fa-check-circle text-success"></i> 
            Giá thấp nhất: <strong>${priceFormatted}đ</strong>
        `);
    }

    // ============================================================================
    // 10. Form validation
    // ============================================================================
    $('#discountForm').on('submit', function(e) {
        const startDate = new Date($('#startDate').val());
        const endDate = new Date($('#endDate').val());
        
        if (endDate <= startDate) {
            e.preventDefault();
            alert('⚠️ Ngày kết thúc phải sau ngày bắt đầu!');
            return false;
        }
        
        const discountValue = parseFloat($('#discountValue').val());
        const discountType = $('#discountType').val();
        
        if (discountType === 'percentage' && discountValue > 100) {
            e.preventDefault();
            alert('⚠️ Giá trị giảm % không được vượt quá 100!');
            return false;
        }
        
        if (!$appliedToID.val()) {
            e.preventDefault();
            alert('⚠️ Vui lòng chọn đầy đủ đối tượng áp dụng!');
            return false;
        }
        
        console.log('✅ Form validation passed');
        return true;
    });

    // ============================================================================
    // 11. Trigger change nếu có old value
    // ============================================================================
    @if(old('appliedToType'))
        $appliedToType.trigger('change');
        
        @if(old('appliedToType') == 'category' && old('appliedToID'))
            setTimeout(() => {
                $('#categorySelect').val('{{ old('appliedToID') }}').trigger('change');
            }, 100);
        @endif
        
        @if(old('appliedToType') == 'brand' && old('appliedToID'))
            setTimeout(() => {
                $('#brandSelect').val('{{ old('appliedToID') }}').trigger('change');
            }, 100);
        @endif
        
        @if(old('appliedToType') == 'product' && old('appliedToID'))
            setTimeout(() => {
                $('#productSelect').val('{{ old('appliedToID') }}').trigger('change');
            }, 100);
        @endif
    @endif
});
</script>
@stop