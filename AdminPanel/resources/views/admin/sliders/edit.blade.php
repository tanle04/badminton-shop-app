@extends('adminlte::page')
@section('title', 'Chỉnh sửa Slider: ' . ($slider->title ?? 'ID#' . $slider->sliderID))
@section('content_header')
    <h1>Chỉnh sửa Slider: {{ $slider->title ?? 'ID#' . $slider->sliderID }}</h1>
@stop
@section('content')
    <form action="{{ route('admin.sliders.update', $slider) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card card-info">
            <div class="card-header"><h3 class="card-title">Thông tin Slider/Banner</h3></div>
            <div class="card-body">
                @include('admin.sliders._form', ['slider' => $slider])
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Cập nhật Slider</button>
                <a href="{{ route('admin.sliders.index') }}" class="btn btn-default">Hủy</a>
            </div>
        </div>
    </form>
@stop