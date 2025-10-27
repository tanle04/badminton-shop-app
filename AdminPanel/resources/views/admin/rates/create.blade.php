@extends('adminlte::page')

@section('title', 'Thêm Mức phí Vận chuyển mới')

@section('content_header')
    <h1>Thêm Mức phí Vận chuyển mới</h1>
@stop

@section('content')
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.rates.store') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="carrierID">Đơn vị Vận chuyển:</label>
                {{-- Controller phải truyền biến $carriers: return view('admin.rates.create', compact('carriers')); --}}
                <select name="carrierID" id="carrierID" class="form-control @error('carrierID') is-invalid @enderror" required>
                    <option value="">-- Chọn Carrier --</option>
                    @foreach ($carriers as $carrier)
                        <option value="{{ $carrier->carrierID }}" {{ old('carrierID') == $carrier->carrierID ? 'selected' : '' }}>
                            {{ $carrier->carrierName }}
                        </option>
                    @endforeach
                </select>
                @error('carrierID')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="serviceName">Tên Dịch vụ:</label>
                <input type="text" name="serviceName" id="serviceName" class="form-control @error('serviceName') is-invalid @enderror" value="{{ old('serviceName') }}" required>
                @error('serviceName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="price">Phí (VND):</label>
                <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required min="0" step="100">
                @error('price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="estimatedDelivery">Thời gian Dự kiến:</label>
                <input type="text" name="estimatedDelivery" id="estimatedDelivery" class="form-control @error('estimatedDelivery') is-invalid @enderror" value="{{ old('estimatedDelivery') }}" placeholder="Ví dụ: 2-4 ngày" required>
                @error('estimatedDelivery')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Lưu Rate</button>
            <a href="{{ route('admin.rates.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
@stop