@extends('adminlte::page')

@section('title', 'Quản Lý Tài Khoản Nhân Viên')

@section('content_header')
    <h1 class="m-0 text-dark">Quản Lý Tài Khoản Nhân Viên</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Thêm Nhân Viên
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 10px">ID</th>
                                <th>Tên</th>
                                <th>Email</th>
                                <th>Vai trò</th>
                                <th style="width: 150px">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $employee)
                                <tr>
                                    <td>{{ $employee->id }}</td>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $employee->email }}</td>
                                    <td>
                                        <span class="badge 
                                            @if($employee->role === 'admin') bg-danger 
                                            @elseif($employee->role === 'staff') bg-success 
                                            @else bg-info 
                                            @endif">
                                            {{ ucfirst($employee->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.employees.edit', $employee) }}" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Sửa">
                                            <i class="fa fa-lg fa-fw fa-pen"></i>
                                        </a>
                                        
                                        <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-default text-danger mx-1 shadow" title="Xóa" 
                                                onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản {{ $employee->name }}?');"
                                                {{-- Ngăn Admin tự xóa tài khoản của mình --}}
                                                @if($employee->id === auth('admin')->id()) disabled @endif>
                                                <i class="fa fa-lg fa-fw fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">
                        {{ $employees->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
