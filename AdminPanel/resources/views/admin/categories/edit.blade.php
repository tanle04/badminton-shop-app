@extends('adminlte::page')

@section('title', 'Chỉnh sửa Danh mục')

@section('content_header')
<h1>Chỉnh sửa Danh mục: {{ $category->categoryName }}</h1>
@stop

@section('content')
<div class="row">
<div class="col-md-6">
<div class="card card-warning">
<div class="card-header">
<h3 class="card-title">Thông tin Danh mục</h3>
</div>
<!-- Bắt đầu form -->
<form action="{{ route('admin.categories.update', $category->categoryID) }}" method="POST">
@csrf
@method('PUT') {{-- Bắt buộc phải có để gửi request PUT đến Controller --}}

                <div class="card-body">
                    <div class="form-group">
                        <label for="categoryName">Tên Danh mục</label>
                        {{-- Lấy giá trị cũ từ DB hoặc từ session validation error --}}
                        <input type="text" name="categoryName" class="form-control @error('categoryName') is-invalid @enderror" id="categoryName" placeholder="Nhập tên danh mục" value="{{ old('categoryName', $category->categoryName) }}" required>
                        
                        @error('categoryName')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">Cập nhật</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-default">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>


@stop