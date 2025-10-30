@extends('adminlte::page')

@section('title', 'Tạo Slider Mới')

@section('content_header')
    <div class="row align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">
                <i class="fas fa-plus-circle text-primary"></i> Tạo Slider Mới
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sliders.index') }}">Sliders</a></li>
                <li class="breadcrumb-item active">Tạo mới</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    {{-- Errors --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-ban"></i> Có lỗi xảy ra!</h5>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data" id="slider-form">
        @csrf
        
        <div class="card card-primary card-outline shadow">
            <div class="card-header bg-white">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Thông tin Slider/Banner
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            
            <div class="card-body">
                @include('admin.sliders._form')
            </div>
            
            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Lưu Slider
                </button>
                <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy
                </a>
            </div>
        </div>
    </form>
@stop

@section('css')
    <style>
        .card {
            border-radius: 10px;
        }
        
        .btn {
            border-radius: 5px;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Form validation
            $('#slider-form').on('submit', function(e) {
                const imageInput = $('#imageUrl');
                
                if (!imageInput.val()) {
                    e.preventDefault();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Thiếu hình ảnh!',
                        text: 'Vui lòng chọn hình ảnh cho slider'
                    });
                    
                    return false;
                }
                
                // Show loading
                Swal.fire({
                    title: 'Đang tải lên...',
                    html: 'Vui lòng đợi trong giây lát',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            });
        });
    </script>
@stop