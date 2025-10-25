@extends('adminlte::page')

@section('title', 'Quản lý Thương hiệu')

@section('content_header')
<h1>Quản lý Thương hiệu</h1>
@stop

@section('content')
<div class="row">
<div class="col-12">

        {{-- Hiển thị thông báo thành công hoặc lỗi --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Danh sách Thương hiệu</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.brands.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Thêm mới
                    </a>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <table id="brandsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Thương hiệu</th>
                            <th>Số SP</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($brands as $brand)
                            <tr>
                                <td>{{ $brand->brandID }}</td>
                                <td>{{ $brand->brandName }}</td>
                                <td>{{ $brand->products->count() }}</td>
                                <td class="text-center">
                                    <a href="{{ route('admin.brands.edit', $brand->brandID) }}" class="btn btn-xs btn-warning mr-1" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.brands.destroy', $brand->brandID) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thương hiệu này không?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
</div>


@stop

@section('js')
{{-- Sử dụng plugin DataTable của AdminLTE --}}
<script>
$(document).ready(function() {
$('#brandsTable').DataTable({
"paging": true,
"lengthChange": false,
"searching": true,
"ordering": true,
"info": true,
"autoWidth": false,
"responsive": true,
});
});
</script>
@stop