@extends('adminlte::page')

@section('title', 'Chỉnh sửa Mức phí Vận chuyển')

@section('content_header')
    <h1>Chỉnh sửa Mức phí: {{ $rate->serviceName }}</h1>
@stop

@section('content')
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="{{ route('admin.rates.update', $rate) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="carrierID">Đơn vị Vận chuyển:</label>
                {{-- Controller phải truyền biến $carriers --}}
                <select name="carrierID" id="carrierID" class="form-control @error('carrierID') is-invalid @enderror" required>
                    <option value="">-- Chọn Carrier --</option>
                    @foreach ($carriers as $carrier)
                        <option value="{{ $carrier->carrierID }}" {{ old('carrierID', $rate->carrierID) == $carrier->carrierID ? 'selected' : '' }}>
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
                <input type="text" name="serviceName" id="serviceName" class="form-control @error('serviceName') is-invalid @enderror" value="{{ old('serviceName', $rate->serviceName) }}" required>
                @error('serviceName')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="price">Phí (VND):</label>
                <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $rate->price) }}" required min="0" step="100">
                @error('price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="estimatedDelivery">Thời gian Dự kiến:</label>
                <input type="text" name="estimatedDelivery" id="estimatedDelivery" class="form-control @error('estimatedDelivery') is-invalid @enderror" value="{{ old('estimatedDelivery', $rate->estimatedDelivery) }}" placeholder="Ví dụ: 2-4 ngày" required>
                @error('estimatedDelivery')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary">Cập nhật Rate</button>
            <a href="{{ route('admin.rates.index') }}" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
@stop