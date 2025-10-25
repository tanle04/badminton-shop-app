@extends('adminlte::page')

@section('title', 'Tạo Mã giảm giá Mới')

@section('content_header')
    <h1>Tạo Mã giảm giá Mới</h1>
@stop

@section('content')
    <form action="{{ route('admin.vouchers.store') }}" method="POST">
        @csrf
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Thông tin Mã giảm giá</h3></div>
            <div class="card-body">
                {{-- Bao gồm form chung, truyền biến $voucher là null/trống --}}
                @include('admin.vouchers._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Lưu Voucher</button>
                <a href="{{ route('admin.vouchers.index') }}" class="btn btn-default">Hủy</a>
            </div>
        </div>
    </form>
@stop