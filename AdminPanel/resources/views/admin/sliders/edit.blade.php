@extends('adminlte::page')

@section('title', 'Chỉnh sửa Slider: ' . ($slider->title ?? 'ID#' . $slider->sliderID))

@section('content_header')
    <div class="row align-items-center">
        <div class="col-sm-6">
            <h1 class="m-0">
                <i class="fas fa-edit text-warning"></i> 
                Chỉnh sửa Slider: {{ $slider->title ?? 'ID#' . $slider->sliderID }}
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.sliders.index') }}">Sliders</a></li>
                <li class="breadcrumb-item active">Chỉnh sửa</li>
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

    <form action="{{ route('admin.sliders.update', $slider) }}" method="POST" enctype="multipart/form-data" id="slider-form">
        @csrf
        @method('PUT')
        
        <div class="card card-warning card-outline shadow">
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
                @include('admin.sliders._form', ['slider' => $slider])
            </div>
            
            <div class="card-footer bg-white">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Cập nhật Slider
                </button>
                <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                
                <button type="button" class="btn btn-danger float-right" id="btn-delete">
                    <i class="fas fa-trash"></i> Xóa Slider
                </button>
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
            const CSRF_TOKEN = $('meta[name="csrf-token"]').attr('content');
            
            // Form submission
            $('#slider-form').on('submit', function(e) {
                // Show loading
                Swal.fire({
                    title: 'Đang cập nhật...',
                    html: 'Vui lòng đợi trong giây lát',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            });
            
            // Delete button
            $('#btn-delete').on('click', function() {
                Swal.fire({
                    title: 'Xác nhận xóa?',
                    html: 'Bạn có chắc muốn xóa slider<br><strong>"{{ $slider->title ?: 'này' }}"</strong>?<br><small class="text-muted">Hành động này không thể hoàn tác!</small>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-trash"></i> Xóa',
                    cancelButtonText: '<i class="fas fa-times"></i> Hủy',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteSlider();
                    }
                });
            });
            
            function deleteSlider() {
                Swal.fire({
                    title: 'Đang xóa...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                $.ajax({
                    url: '{{ route("admin.sliders.destroy", $slider) }}',
                    method: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: CSRF_TOKEN
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã xóa!',
                            text: 'Slider đã được xóa thành công',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        setTimeout(() => {
                            window.location.href = '{{ route("admin.sliders.index") }}';
                        }, 2000);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi!',
                            text: xhr.responseJSON?.message || 'Không thể xóa slider'
                        });
                    }
                });
            }
        });
    </script>
@stop