@extends('adminlte::page')

@section('title', 'Chỉnh sửa Thương hiệu')

@section('content_header')
<h1>Chỉnh sửa Thương hiệu: {{ $brand->brandName }}</h1>
@stop

@section('content')
<div class="row">
<div class="col-md-6">
<div class="card card-warning">
<div class="card-header">
<h3 class="card-title">Thông tin Thương hiệu</h3>
</div>
<!-- Bắt đầu form -->
<form action="{{ route('admin.brands.update', $brand->brandID) }}" method="POST">
@csrf
@method('PUT') {{-- Bắt buộc phải có để gửi request PUT đến Controller --}}

                <div class="card-body">
                    <div class="form-group">
                        <label for="brandName">Tên Thương hiệu</label>
                        {{-- Lấy giá trị cũ từ DB hoặc từ session validation error --}}
                        <input type="text" name="brandName" class="form-control @error('brandName') is-invalid @enderror" id="brandName" placeholder="Nhập tên thương hiệu" value="{{ old('brandName', $brand->brandName) }}" required>
                        
                        @error('brandName')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Cập nhật</button>
                    <a href="{{ route('admin.brands.index') }}" class="btn btn-default">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>


@stop