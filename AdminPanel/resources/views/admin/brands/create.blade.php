@extends('adminlte::page')

@section('title', 'Thêm Thương hiệu mới')

@section('content_header')
<h1>Thêm Thương hiệu mới</h1>
@stop

@section('content')
<div class="row">
<div class="col-md-6">
<div class="card card-primary">
<div class="card-header">
<h3 class="card-title">Thông tin Thương hiệu</h3>
</div>
<!-- Bắt đầu form -->
<form action="{{ route('admin.brands.store') }}" method="POST">
@csrf
<div class="card-body">
<div class="form-group">
<label for="brandName">Tên Thương hiệu</label>
<input type="text" name="brandName" class="form-control @error('brandName') is-invalid @enderror" id="brandName" placeholder="Nhập tên thương hiệu" value="{{ old('brandName') }}" required>
@error('brandName')
<span class="invalid-feedback" role="alert">
<strong>{{ $message }}</strong>
</span>
@enderror
</div>
</div>
<!-- /.card-body -->

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Lưu Thương hiệu</button>
                    <a href="{{ route('admin.brands.index') }}" class="btn btn-default">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>


@stop