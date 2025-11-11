@extends('adminlte::page')

@section('title', 'Chỉnh Sửa Tài Khoản Khách Hàng')

@section('content_header')
    <h1 class="m-0 text-dark">Chỉnh Sửa Tài Khoản: {{ $customer->fullName }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('admin.customers.update', $customer->customerID) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        {{-- Họ tên --}}
                        <div class="form-group">
                            <label for="name">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" value="{{ old('name', $customer->fullName) }}" required>
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-group">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{ old('email', $customer->email) }}" required>
                            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Số điện thoại --}}
                        <div class="form-group">
                            <label for="phone">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" id="phone" value="{{ old('phone', $customer->phone) }}" required>
                            @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Trạng thái xác thực --}}
                        <div class="form-group">
                            <label for="isEmailVerified">Trạng thái xác thực Email</label>
                            <select name="isEmailVerified" id="isEmailVerified" class="form-control @error('isEmailVerified') is-invalid @enderror" required>
                                <option value="0" {{ old('isEmailVerified', $customer->isEmailVerified) == 0 ? 'selected' : '' }}>Chưa xác thực</option>
                                <option value="1" {{ old('isEmailVerified', $customer->isEmailVerified) == 1 ? 'selected' : '' }}>Đã xác thực</option>
                            </select>
                            @error('isEmailVerified') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        
                        <hr>
                        <p class="text-muted"><i class="fas fa-info-circle"></i> Bỏ trống mật khẩu nếu không muốn thay đổi.</p>

                        {{-- Mật khẩu (Không bắt buộc) --}}
                        <div class="form-group">
                            <label for="password">Mật khẩu mới</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password">
                            <small class="form-text text-muted">Tối thiểu 6 ký tự</small>
                            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Xác nhận mật khẩu --}}
                        <div class="form-group">
                            <label for="password_confirmation">Xác nhận Mật khẩu mới</label>
                            <input type="password" name="password_confirmation" class="form-control" id="password_confirmation">
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập Nhật
                        </button>
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-default">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
