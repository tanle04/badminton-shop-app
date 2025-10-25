@extends('adminlte::page')

@section('title', 'Thêm Danh mục mới')

@section('content_header')
<h1>Thêm Danh mục mới</h1>
@stop

@section('content')
<div class="row">
<div class="col-md-6">
<div class="card card-primary">
<div class="card-header">
<h3 class="card-title">Thông tin Danh mục</h3>
</div>
<!-- Bắt đầu form -->
<form action="{{ route('admin.categories.store') }}" method="POST">
@csrf
<div class="card-body">
<div class="form-group">
<label for="categoryName">Tên Danh mục</label>
<input type="text" name="categoryName" class="form-control @error('categoryName') is-invalid @enderror" id="categoryName" placeholder="Ví dụ: Vợt cầu lông" value="{{ old('categoryName') }}" required>
@error('categoryName')
<span class="invalid-feedback" role="alert">
<strong>{{ $message }}</strong>
</span>
@enderror
</div>
</div>
<!-- /.card-body -->

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Lưu Danh mục</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-default">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>


@stop