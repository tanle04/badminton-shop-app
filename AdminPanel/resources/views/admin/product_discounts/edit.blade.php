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

    <form action="/admin/product-discounts/{{ $discount->discountID }}" 
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
                                        <option value="percentage" {{ old('discountType', $discount->discountType) == 'percentage' ? 'selected' : '' }}>
                                            <i class="fas fa-percent"></i> Phần trăm (%)
                                        </option>
                                        <option value="fixed" {{ old('discountType', $discount->discountType) == 'fixed' ? 'selected' : '' }}>
                                            <i class="fas fa-dollar-sign"></i> Cố định (VNĐ)
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
                                        <input type="datetime-local" 
                                               name="startDate" 
                                               id="startDate" 
                                               class="form-control @error('startDate') is-invalid @enderror" 
                                               value="{{ old('startDate', $discount->startDate ? \Carbon\Carbon::parse($discount->startDate)->format('Y-m-d\TH:i') : '') }}" 
                                               required>
                                    </div>
                                    @error('startDate')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
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
                                               value="{{ old('endDate', $discount->endDate ? \Carbon\Carbon::parse($discount->endDate)->format('Y-m-d\TH:i') : '') }}" 
                                               required>
                                    </div>
                                    @error('endDate')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
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
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Hướng dẫn:</strong> Chọn loại đối tượng và nhập ID tương ứng. 
                            Ví dụ: Chọn "Product" và nhập ID sản phẩm cần giảm giá.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="appliedToType">
                                        Áp dụng cho <span class="text-danger">*</span>
                                    </label>
                                    <select name="appliedToType" 
                                            id="appliedToType" 
                                            class="form-control @error('appliedToType') is-invalid @enderror" 
                                            required>
                                        <option value="">-- Chọn loại --</option>
                                        @php
                                            $types = [
                                                'category' => ['icon' => 'fa-folder', 'label' => 'Danh mục'],
                                                'brand' => ['icon' => 'fa-copyright', 'label' => 'Thương hiệu'],
                                                'product' => ['icon' => 'fa-box', 'label' => 'Sản phẩm'],
                                                'variant' => ['icon' => 'fa-boxes', 'label' => 'Biến thể']
                                            ];
                                        @endphp
                                        @foreach($types as $type => $info)
                                            <option value="{{ $type }}" 
                                                    {{ old('appliedToType', $discount->appliedToType) == $type ? 'selected' : '' }}>
                                                {{ $info['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('appliedToType')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="appliedToID">
                                        ID Đối tượng <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">
                                                <i class="fas fa-hashtag"></i>
                                            </span>
                                        </div>
                                        <input type="number" 
                                               name="appliedToID" 
                                               id="appliedToID" 
                                               class="form-control @error('appliedToID') is-invalid @enderror" 
                                               value="{{ old('appliedToID', $discount->appliedToID) }}" 
                                               required 
                                               placeholder="Nhập ID">
                                    </div>
                                    @error('appliedToID')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                    <small class="form-text text-muted" id="applied-to-hint">
                                        Nhập ID của đối tượng được chọn ở trên
                                    </small>
                                </div>
                            </div>
                        </div>
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
                                <br>
                                <small class="text-muted">
                                    Bật để chương trình có hiệu lực ngay lập tức
                                </small>
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

                            <dt class="col-sm-6">Ngày tạo:</dt>
                            <dd class="col-sm-6">
                                <small>{{ \Carbon\Carbon::parse($discount->created_at)->format('d/m/Y H:i') }}</small>
                            </dd>

                            <dt class="col-sm-6">Cập nhật:</dt>
                            <dd class="col-sm-6">
                                <small>{{ \Carbon\Carbon::parse($discount->updated_at)->format('d/m/Y H:i') }}</small>
                            </dd>

                            @php
                                $today = now();
                                $start = \Carbon\Carbon::parse($discount->startDate);
                                $end = \Carbon\Carbon::parse($discount->endDate);
                            @endphp

                            <dt class="col-sm-6">Trạng thái:</dt>
                            <dd class="col-sm-6">
                                @if(!$discount->isActive)
                                    <span class="badge badge-danger">Tạm ngưng</span>
                                @elseif($end < $today)
                                    <span class="badge badge-warning">Hết hạn</span>
                                @elseif($start > $today)
                                    <span class="badge badge-info">Chưa bắt đầu</span>
                                @else
                                    <span class="badge badge-success">Đang hoạt động</span>
                                @endif
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
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    console.log('✅ Form ready');
    
    const $maxDiscountField = $('#maxDiscountAmountField');
    const $discountType = $('#discountType');
    const $valueUnit = $('#value-unit');
    const $appliedToType = $('#appliedToType');
    const $appliedToHint = $('#applied-to-hint');

    // ============================================================================
    // Toggle Max Discount Amount field
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
    // Update hint based on applied type
    // ============================================================================
    function updateAppliedToHint() {
        const type = $appliedToType.val();
        const hints = {
            'category': 'Nhập ID danh mục (ví dụ: 1 = Vợt cầu lông)',
            'brand': 'Nhập ID thương hiệu (ví dụ: 2 = Victor)',
            'product': 'Nhập ID sản phẩm (ví dụ: 15 = Vợt Victor TK9900)',
            'variant': 'Nhập ID biến thể (ví dụ: 45 = Size L của sản phẩm X)'
        };
        
        $appliedToHint.html(`
            <i class="fas fa-lightbulb"></i> ${hints[type] || 'Chọn loại trước'}
        `);
    }
    
    updateAppliedToHint();
    $appliedToType.on('change', updateAppliedToHint);

    // ============================================================================
    // Form validation
    // ============================================================================
    $('#discountForm').on('submit', function(e) {
        const startDate = new Date($('#startDate').val());
        const endDate = new Date($('#endDate').val());
        
        if (endDate <= startDate) {
            e.preventDefault();
            alert('Ngày kết thúc phải sau ngày bắt đầu!');
            return false;
        }
        
        const discountValue = parseFloat($('#discountValue').val());
        const discountType = $('#discountType').val();
        
        if (discountType === 'percentage' && discountValue > 100) {
            e.preventDefault();
            alert('Giá trị giảm % không được vượt quá 100!');
            return false;
        }
        
        console.log('✅ Form validation passed, submitting...');
        return true;
    });
});
</script>
@stop