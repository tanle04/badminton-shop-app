@extends('adminlte::page')

@section('title', 'Thêm Tài Khoản Nhân Viên Mới')

@section('content_header')
    <h1 class="m-0 text-dark">Thêm Tài Khoản Nhân Viên Mới</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.employees.store') }}" method="POST">
                        @csrf
                        
                        {{-- Tên --}}
                        <div class="form-group">
                            <label for="name">Tên Nhân Viên</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" value="{{ old('name') }}" required>
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{ old('email') }}" required>
                            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Vai trò (Role) --}}
                        <div class="form-group">
                            <label for="role">Vai trò</label>
                            <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
                                <option value="" disabled selected>Chọn vai trò</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin (Toàn Quyền)</option>
                                <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff (Quản lý Kho)</option>
                                <option value="marketing" {{ old('role') == 'marketing' ? 'selected' : '' }}>Marketing (Quản lý Khuyến Mãi)</option>
                            </select>
                            @error('role') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        
                        {{-- Mật khẩu --}}
                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password" required>
                            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Xác nhận mật khẩu --}}
                        <div class="form-group">
                            <label for="password_confirmation">Xác nhận Mật khẩu</label>
                            <input type="password" name="password_confirmation" class="form-control" id="password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Tạo Tài Khoản</button>
                        <a href="{{ route('admin.employees.index') }}" class="btn btn-default">Hủy</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
