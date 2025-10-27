@extends('adminlte::page')

@section('title', 'Cấu hình Vận chuyển')

@section('content_header')
    <h1>Cấu hình Chính sách Vận chuyển</h1>
@stop

@section('content')
<div class="card shadow mb-4">
    <div class="card-header">
        <h3 class="card-title">Ngưỡng Miễn phí Vận chuyển (Free Ship)</h3>
    </div>

    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        
        <form action="{{ route('admin.shipping.config.update') }}" method="POST">
            @csrf
            {{-- Sử dụng @method('PUT') vì bạn định nghĩa route là PUT --}}
            @method('PUT') 
            
            <div class="form-group">
                <label for="free_ship_threshold">Ngưỡng đơn hàng tối thiểu để Free Ship (VND)</label>
                <input 
                    type="number" 
                    name="free_ship_threshold" 
                    id="free_ship_threshold" 
                    class="form-control @error('free_ship_threshold') is-invalid @enderror" 
                    value="{{ old('free_ship_threshold', $freeShipThreshold ?? 0) }}" 
                    required 
                    min="0"
                    step="1000"
                    placeholder="Ví dụ: 2000000 cho 2 triệu đồng"
                >
                <small class="form-text text-muted">Đơn hàng có tổng giá trị hàng hóa (Subtotal) từ ngưỡng này trở lên sẽ được miễn phí vận chuyển.</small>
                
                @error('free_ship_threshold')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Lưu Cấu hình</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Quay lại Dashboard</a>
        </form>
    </div>
</div>
@stop