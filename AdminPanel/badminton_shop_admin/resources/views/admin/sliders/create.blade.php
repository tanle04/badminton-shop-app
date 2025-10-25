@extends('adminlte::page')
@section('title', 'Tạo Slider Mới')
@section('content_header')
    <h1>Tạo Slider Mới</h1>
@stop
@section('content')
    <form action="{{ route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Thông tin Slider/Banner</h3></div>
            <div class="card-body">
                @include('admin.sliders._form')
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Lưu Slider</button>
                <a href="{{ route('admin.sliders.index') }}" class="btn btn-default">Hủy</a>
            </div>
        </div>
    </form>
@stop