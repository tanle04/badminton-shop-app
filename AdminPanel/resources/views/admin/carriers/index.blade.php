@extends('adminlte::page')

@section('title', 'Quản lý Đơn vị Vận chuyển')

@section('content_header')
    <h1>Quản lý Đơn vị Vận chuyển (Carriers)</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách Carriers</h3>
        <div class="card-tools">
            <a href="{{ route('admin.carriers.create') }}" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Thêm Carrier mới
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card-body p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th style="width: 10px">ID</th>
                    <th>Tên Carrier</th>
                    <th>Trạng thái</th>
                    <th style="width: 150px">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($carriers as $carrier)
                <tr>
                    <td>{{ $carrier->carrierID }}</td>
                    <td>{{ $carrier->carrierName }}</td>
                    <td>
                        <span class="badge {{ $carrier->isActive ? 'badge-success' : 'badge-danger' }}">
                            {{ $carrier->isActive ? 'Hoạt động' : 'Tạm dừng' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.carriers.edit', $carrier) }}" class="btn btn-info btn-xs">Sửa</a>
                        <form action="{{ route('admin.carriers.destroy', $carrier) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Xóa Carrier sẽ xóa cả các mức phí liên quan (nếu có). Bạn có chắc chắn muốn xóa không?')">Xóa</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
        {{ $carriers->links('pagination::bootstrap-4') }}
    </div>
</div>
@stop