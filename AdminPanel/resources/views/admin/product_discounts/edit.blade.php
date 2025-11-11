{{-- resources/views/admin/product_discounts/edit.blade.php --}}
@extends('adminlte::page')

@section('title', 'Chỉnh sửa Chương trình Giảm giá')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-edit"></i> Chỉnh sửa Chương trình Giảm giá
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.product-discounts.index') }}">Giảm giá</a></li>
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

{{-- ĐÂY LÀ CODE ĐÚNG --}}
<form action="{{ route('admin.product-discounts.update', $discount->discountID) }}" 
      method="POST" 
      id="discountForm">
        @csrf
        @method('PUT')
        
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
                        {{-- Tên chương trình --}}
                        <div class="form-group">
                            <label for="discountName">
                                Tên Chương trình <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="discountName" 
                                   id="discountName" 
                                   class="form-control @error('discountName') is-invalid @enderror" 
                                   value="{{ old('discountName', $discount->discountName) }}" 
                                   required
                                   placeholder="Ví dụ: Flash Sale cuối tuần">
                            @error('discountName')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
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
                                        <option value="percentage" {{ old('discountType', $discount->discountType) == 'percentage' ? 'selected' : '' }}>
                                            Phần trăm (%)
                                        </option>
                                        <option value="fixed" {{ old('discountType', $discount->discountType) == 'fixed' ? 'selected' : '' }}>
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
                                               value="{{ old('discountValue', $discount->discountValue) }}" 
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
                                    <small class="form-text text-muted" id="price-hint">
                                        <i class="fas fa-info-circle"></i> Đang load thông tin giá...
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
                                               value="{{ old('maxDiscountAmount', $discount->maxDiscountAmount) }}" 
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
                                        @php
                                            $startDateFormatted = '';
                                            if ($discount->startDate) {
                                                try {
                                                    $startDateFormatted = \Carbon\Carbon::parse($discount->startDate)
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
                                               class="form-control @error('startDate') is-invalid @enderror" 
                                               value="{{ old('startDate', $startDateFormatted) }}" 
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
                                        @php
                                            $endDateFormatted = '';
                                            if ($discount->endDate) {
                                                try {
                                                    $endDateFormatted = \Carbon\Carbon::parse($discount->endDate)
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
                                               class="form-control @error('endDate') is-invalid @enderror" 
                                               value="{{ old('endDate', $endDateFormatted) }}" 
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
                            <strong>Lưu ý:</strong> Thay đổi đối tượng sẽ ảnh hưởng đến toàn bộ chiến dịch đang chạy.
                        </div>

                        {{-- Chọn loại --}}
                        <div class="form-group">
                            <label for="appliedToType">
                                Loại đối tượng <span class="text-danger">*</span>
                            </label>
                            <select name="appliedToType" 
                                    id="appliedToType" 
                                    class="form-control @error('appliedToType') is-invalid @enderror" 
                                    required>
                                <option value="">-- Chọn loại đối tượng --</option>
                                <option value="category" {{ old('appliedToType', $discount->appliedToType) == 'category' ? 'selected' : '' }}>
                                    Danh mục
                                </option>
                                <option value="brand" {{ old('appliedToType', $discount->appliedToType) == 'brand' ? 'selected' : '' }}>
                                    Thương hiệu
                                </option>
                                <option value="product" {{ old('appliedToType', $discount->appliedToType) == 'product' ? 'selected' : '' }}>
                                    Sản phẩm
                                </option>
                                <option value="variant" {{ old('appliedToType', $discount->appliedToType) == 'variant' ? 'selected' : '' }}>
                                    Biến thể cụ thể
                                </option>
                            </select>
                            @error('appliedToType')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <hr>

                        {{-- Chọn Category/Brand --}}
                        <div id="select-category" class="form-group" style="display: none;">
                            <label for="categorySelect">Chọn Danh mục <span class="text-danger">*</span></label>
                            <select id="categorySelect" class="form-control">
                                <option value="">-- Chọn danh mục --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->categoryID }}" 
                                            {{ old('appliedToID', $discount->appliedToType == 'category' ? $discount->appliedToID : '') == $category->categoryID ? 'selected' : '' }}>
                                        {{ $category->categoryName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="select-brand" class="form-group" style="display: none;">
                            <label for="brandSelect">Chọn Thương hiệu <span class="text-danger">*</span></label>
                            <select id="brandSelect" class="form-control">
                                <option value="">-- Chọn thương hiệu --</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->brandID }}"
                                            {{ old('appliedToID', $discount->appliedToType == 'brand' ? $discount->appliedToID : '') == $brand->brandID ? 'selected' : '' }}>
                                        {{ $brand->brandName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Chọn Product --}}
                        <div id="select-product" class="form-group" style="display: none;">
                            <label for="productSelect">
                                <span id="product-label">Chọn Sản phẩm</span> <span class="text-danger">*</span>
                            </label>
                            <select id="productSelect" class="form-control">
                                <option value="">-- Chọn sản phẩm --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->productID }}"
                                            {{ old('appliedToID', $selectedProductID ?? ($discount->appliedToType == 'product' ? $discount->appliedToID : '')) == $product->productID ? 'selected' : '' }}>
                                        {{ $product->productName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Chọn Variant --}}
                        <div id="select-variant" class="form-group" style="display: none;">
                            <label for="variantSelect">Chọn Biến thể cụ thể <span class="text-danger">*</span></label>
                            <select id="variantSelect" class="form-control">
                                <option value="">-- Chọn sản phẩm trước --</option>
                                @if(isset($variantsForProduct))
                                    @foreach($variantsForProduct as $variant)
                                        <option value="{{ $variant->variantID }}"
                                                {{ old('appliedToID', $discount->appliedToType == 'variant' ? $discount->appliedToID : '') == $variant->variantID ? 'selected' : '' }}>
                                            {{ $variant->sku }} - {{ number_format($variant->price) }}đ
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        {{-- Hidden input --}}
                        <input type="hidden" name="appliedToID" id="appliedToID" value="{{ old('appliedToID', $discount->appliedToID) }}">
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
                                   {{ old('isActive', $discount->isActive) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="isActive">
                                <strong>Kích hoạt chương trình</strong>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Card 4: Thông tin --}}
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line"></i> Thông tin
                        </h3>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-6">ID:</dt>
                            <dd class="col-sm-6">
                                <span class="badge badge-primary">#{{ $discount->discountID }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>

                {{-- Card 5: Thông tin giá --}}
                <div class="card card-info card-outline" id="price-info-card" style="display: none;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-dollar-sign"></i> Thông tin giá
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="price-info-content">
                            <p class="text-muted">Đang tải...</p>
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
                        <button type="submit" class="btn btn-warning btn-lg">
                            <i class="fas fa-save"></i> Cập nhật Chương trình
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
    console.log('✅ Form edit ready');
    
    const $appliedToType = $('#appliedToType');
    const $categorySelect = $('#select-category');
    const $brandSelect = $('#select-brand');
    const $productSelect = $('#select-product');
    const $variantSelect = $('#select-variant');
    const $appliedToID = $('#appliedToID');
    const $discountType = $('#discountType');
    const $maxDiscountField = $('#maxDiscountAmountField');
    const $valueUnit = $('#value-unit');
    const $priceInfoCard = $('#price-info-card');
    const $priceInfoContent = $('#price-info-content');
    const $priceHint = $('#price-hint');

    // Toggle Max Discount
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

    // Xử lý khi đổi loại đối tượng (tương tự create.blade.php)
    $appliedToType.on('change', function() {
        const type = $(this).val();
        
        $categorySelect.hide();
        $brandSelect.hide();
        $productSelect.hide();
        $variantSelect.hide();
        $priceInfoCard.hide();
        
        switch(type) {
            case 'category':
                $categorySelect.show();
                break;
            case 'brand':
                $brandSelect.show();
                break;
            case 'product':
                $('#product-label').text('Chọn Sản phẩm (Sale tất cả variants)');
                $productSelect.show();
                break;
            case 'variant':
                $('#product-label').text('Chọn Sản phẩm');
                $productSelect.show();
                $variantSelect.show();
                break;
        }
    });

    // Category change
    $('#categorySelect').on('change', function() {
        const categoryID = $(this).val();
        if (categoryID) {
            $appliedToID.val(categoryID);
            fetchMinPrice('category', categoryID);
        }
    });

    // Brand change
    $('#brandSelect').on('change', function() {
        const brandID = $(this).val();
        if (brandID) {
            $appliedToID.val(brandID);
            fetchMinPrice('brand', brandID);
        }
    });

    // Product change
    $('#productSelect').on('change', function() {
        const productID = $(this).val();
        const type = $appliedToType.val();
        
        if (!productID) {
            $appliedToID.val('');
            $priceInfoCard.hide();
            return;
        }
        
        if (type === 'product') {
            $appliedToID.val(productID);
            fetchMinPrice('product', productID);
        } else if (type === 'variant') {
            loadVariants(productID);
        }
    });

    // Variant change
    $('#variantSelect').on('change', function() {
        const variantID = $(this).val();
        if (variantID) {
            $appliedToID.val(variantID);
            fetchMinPrice('variant', variantID);
        }
    });

    // Load variants
    function loadVariants(productID) {
        $('#variantSelect').html('<option value="">Đang tải...</option>');
        
        $.ajax({
            url: '{{ route("admin.product-discounts.get-product-variants", ["id" => ":id"]) }}'.replace(':id', productID),
            method: 'GET',
            success: function(variants) {
                let options = '<option value="">-- Chọn biến thể --</option>';
                
                variants.forEach(v => {
                    const priceFormatted = new Intl.NumberFormat('vi-VN').format(v.price);
                    options += `<option value="${v.variantID}">
                        ${v.sku} - ${priceFormatted}đ (Tồn: ${v.stock})
                    </option>`;
                });
                
                $('#variantSelect').html(options);
                
                // Restore old value if exists
                const oldVariantID = '{{ old("appliedToID", $discount->appliedToType == "variant" ? $discount->appliedToID : "") }}';
                if (oldVariantID) {
                    $('#variantSelect').val(oldVariantID).trigger('change');
                }
            },
            error: function(xhr) {
                console.error('Lỗi load variants:', xhr);
                $('#variantSelect').html('<option value="">Lỗi tải dữ liệu</option>');
            }
        });
    }

    // Fetch min price
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
            }
        });
    }

    // Display price info
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
        `;
        
        $priceInfoContent.html(html);
        $priceInfoCard.fadeIn();
        
        $priceHint.html(`
            <i class="fas fa-check-circle text-success"></i> 
            Giá thấp nhất: <strong>${priceFormatted}đ</strong>
        `);
    }

    // Form validation
    $('#discountForm').on('submit', function(e) {
        if (!$appliedToID.val()) {
            e.preventDefault();
            alert('⚠️ Vui lòng chọn đầy đủ đối tượng áp dụng!');
            return false;
        }
        return true;
    });

    // Trigger initial state
    $appliedToType.trigger('change');
    
    // Fetch initial price info
    const initialType = '{{ $discount->appliedToType }}';
    const initialID = '{{ $discount->appliedToID }}';
    if (initialType && initialID) {
        fetchMinPrice(initialType, initialID);
    }
});
</script>
@stop