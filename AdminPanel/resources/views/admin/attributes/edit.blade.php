@extends('adminlte::page')

@section('title', 'Chỉnh sửa Thuộc tính Sản phẩm')

@section('content_header')
    <h1>Chỉnh sửa Thuộc tính Sản phẩm</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Cập nhật Thuộc tính</h3>
                </div>

                <form action="{{ route('admin.attributes.update', $attribute->attributeID) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="form-group">
                            <label for="attributeName">
                                Tên Thuộc tính 
                                <span class="text-danger">*</span>
                            </label>
                            
                            <input type="text" 
                                   class="form-control @error('attributeName') is-invalid @enderror" 
                                   id="attributeName" 
                                   name="attributeName"
                                   placeholder="Ví dụ: Size, Grip, Trọng lượng, Chất liệu"
                                   value="{{ old('attributeName', $attribute->attributeName) }}" 
                                   required
                                   maxlength="100">
                            
                            @error('attributeName')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror

                            <small class="form-text text-muted">
                                Cập nhật tên thuộc tính (tối đa 100 ký tự)
                            </small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
EOF
cat /mnt/user-data/outputs/CORRECTED_EDIT_COMPLETE.blade.php
Output

@extends('adminlte::page')

@section('title', 'Chỉnh sửa Thuộc tính Sản phẩm')

@section('content_header')
    <h1>Chỉnh sửa Thuộc tính Sản phẩm</h1>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Cập nhật Thuộc tính</h3>
                </div>

                <form action="{{ route('admin.attributes.update', $attribute->attributeID) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="form-group">
                            <label for="attributeName">
                                Tên Thuộc tính 
                                <span class="text-danger">*</span>
                            </label>
                            
                            <input type="text" 
                                   class="form-control @error('attributeName') is-invalid @enderror" 
                                   id="attributeName" 
                                   name="attributeName"
                                   placeholder="Ví dụ: Size, Grip, Trọng lượng, Chất liệu"
                                   value="{{ old('attributeName', $attribute->attributeName) }}" 
                                   required
                                   maxlength="100">
                            
                            @error('attributeName')
                                <div class="invalid-feedback d-block">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </div>
                            @enderror

                            <small class="form-text text-muted">
                                Cập nhật tên thuộc tính (tối đa 100 ký tự)
                            </small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('admin.attributes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection