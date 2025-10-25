@extends('adminlte::page')

@section('title', 'Quản lý Sliders')

@section('content_header')
    <h1>Quản lý Sliders/Banners</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Danh sách Sliders</h3>
            <div class="card-tools">
                <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm Slider
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 10px">ID</th>
                        <th>Ảnh</th>
                        <th>Tiêu đề</th>
                        <th>Trạng thái</th>
                        <th>Liên kết</th>
                        <th>Người tạo</th>
                        <th style="width: 150px">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sliders as $slider)
                    <tr>
                        <td>{{ $slider->sliderID }}</td>
                        <td>
                            @if ($slider->imageUrl)
                                <img src="{{ asset('storage/' . $slider->imageUrl) }}" style="width: 100px; height: 50px; object-fit: cover;">
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $slider->title ?? 'Không tiêu đề' }}</td>
                        <td>
                            <span class="badge {{ $slider->status == 'active' ? 'badge-success' : 'badge-danger' }}">
                                {{ $slider->status }}
                            </span>
                        </td>
                        <td><a href="{{ $slider->backlink }}" target="_blank">{{ $slider->backlink ? Str::limit($slider->backlink, 20) : 'Không có' }}</a></td>
                        <td>{{ $slider->employee->fullName ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-info btn-xs">Sửa</a>
                            <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Bạn có chắc chắn muốn xóa slider này?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $sliders->links('pagination::bootstrap-4') }}
        </div>
    </div>
@stop