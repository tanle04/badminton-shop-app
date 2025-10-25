@extends('adminlte::page')

@section('title', 'Chỉnh Sửa Tài Khoản Nhân Viên')

@section('content_header')
    <h1 class="m-0 text-dark">Chỉnh Sửa Tài Khoản: {{ $employee->name }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.employees.update', $employee) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        {{-- Tên --}}
                        <div class="form-group">
                            <label for="name">Tên Nhân Viên</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" value="{{ old('name', $employee->name) }}" required>
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{ old('email', $employee->email) }}" required>
                            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Vai trò (Role) --}}
                        <div class="form-group">
                            <label for="role">Vai trò</label>
                            <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required
                                {{-- Ngăn Admin tự thay đổi role --}}
                                @if($employee->id === auth('admin')->id()) disabled title="Bạn không thể tự thay đổi vai trò của tài khoản đang đăng nhập." @endif>
                                <option value="admin" {{ old('role', $employee->role) == 'admin' ? 'selected' : '' }}>Admin (Toàn Quyền)</option>
                                <option value="staff" {{ old('role', $employee->role) == 'staff' ? 'selected' : '' }}>Staff (Quản lý Kho)</option>
                                <option value="marketing" {{ old('role', $employee->role) == 'marketing' ? 'selected' : '' }}>Marketing (Quản lý Khuyến Mãi)</option>
                            </select>
                            @error('role') <span class="text-danger">{{ $message }}</span> @enderror
                            {{-- Gửi role hiện tại nếu bị disabled --}}
                            @if($employee->id === auth('admin')->id())
                                <input type="hidden" name="role" value="{{ $employee->role }}">
                            @endif
                        </div>
                        
                        {{-- Mật khẩu (Không bắt buộc) --}}
                        <div class="form-group">
                            <label for="password">Mật khẩu (Để trống nếu không muốn thay đổi)</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password">
                            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Xác nhận mật khẩu --}}
                        <div class="form-group">
                            <label for="password_confirmation">Xác nhận Mật khẩu mới</label>
                            <input type="password" name="password_confirmation" class="form-control" id="password_confirmation">
                        </div>

                        <button type="submit" class="btn btn-primary">Cập Nhật</button>
                        <a href="{{ route('admin.employees.index') }}" class="btn btn-default">Hủy</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
