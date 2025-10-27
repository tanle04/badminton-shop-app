@extends('adminlte::page')

@section('title', 'Thêm Đơn vị Vận chuyển mới')

@section('content_header')
    <h1>Thêm Đơn vị Vận chuyển mới</h1>
@stop

@section('content')
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.carriers.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="carrierName">Tên Carrier:</label>
                <input type="text" name="carrierName" id="carrierName" class="form-control @error('carrierName') is-invalid @enderror" value="{{ old('carrierName') }}" required>
                @error('carrierName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="isActive" name="isActive" value="1" checked>
                    <label class="custom-control-label" for="isActive">Kích hoạt</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Lưu Carrier</button>
            <a href="{{ route('admin.carriers.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
@stop