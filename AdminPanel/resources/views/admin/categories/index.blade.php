@extends('adminlte::page')

@section('title', 'Quản lý Danh mục')

@section('content_header')
<h1>Quản lý Danh mục</h1>
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
                <h3 class="card-title">Danh sách Danh mục Sản phẩm</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Thêm mới
                    </a>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <table id="categoriesTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Danh mục</th>
                            <th>Số SP</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{ $category->categoryID }}</td>
                                <td>{{ $category->categoryName }}</td>
                                {{-- Dùng products()->count() để lấy số sản phẩm, yêu cầu quan hệ trong Category Model --}}
                                <td>{{ $category->products->count() }}</td> 
                                <td class="text-center">
                                    <a href="{{ route('admin.categories.edit', $category->categoryID) }}" class="btn btn-xs btn-warning mr-1" title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.categories.destroy', $category->categoryID) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa danh mục này không?');">
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
$('#categoriesTable').DataTable({
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