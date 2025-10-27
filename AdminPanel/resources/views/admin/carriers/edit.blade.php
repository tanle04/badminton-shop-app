@extends('adminlte::page')

@section('title', 'Chỉnh sửa Đơn vị Vận chuyển')

@section('content_header')
    {{-- Hiển thị tiêu đề ở thanh header của AdminLTE --}}
    <h1>Chỉnh sửa Đơn vị Vận chuyển: {{ $carrier->carrierName }}</h1>
@stop

@section('content')
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.carriers.update', $carrier) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="carrierName">Tên Carrier:</label>
                <input type="text" name="carrierName" id="carrierName" class="form-control @error('carrierName') is-invalid @enderror" value="{{ old('carrierName', $carrier->carrierName) }}" required>
                @error('carrierName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    {{-- Sửa lỗi: Đặt giá trị checked dựa trên $carrier->isActive --}}
                    <input type="checkbox" class="custom-control-input" id="isActive" name="isActive" value="1" {{ $carrier->isActive ? 'checked' : '' }}>
                    <label class="custom-control-label" for="isActive">Kích hoạt</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Cập nhật Carrier</button>
            <a href="{{ route('admin.carriers.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
@stop