@extends('adminlte::page')

@section('title', 'Quản Lý Tài Khoản Nhân Viên')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-shield"></i> Quản lý Nhân viên
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Nhân viên</li>
            </ol>
        </div>
    </div>
@stop

@section('content')
    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-3">
        <div class="col-lg-4 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalEmployeeCount }}</h3>
                    <p>Tổng tài khoản</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <a href="{{ route('admin.employees.index', ['status' => 'all']) }}" class="small-box-footer">
                    Xem tất cả <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $activeEmployeeCount }}</h3>
                    <p>Đang hoạt động</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <a href="{{ route('admin.employees.index', ['status' => 'active']) }}" class="small-box-footer">
                    Chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        
        <div class="col-lg-4 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $inactiveEmployeeCount }}</h3>
                    <p>Đã khóa</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-lock"></i>
                </div>
                <a href="{{ route('admin.employees.index', ['status' => 'inactive']) }}" class="small-box-footer">
                    Chi tiết <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i> 
                @if($status == 'active')
                    Nhân viên Đang hoạt động
                @elseif($status == 'inactive')
                    Nhân viên Đã khóa
                @else
                    Tất cả Nhân viên
                @endif
            </h3>
            <div class="card-tools">
                {{-- Filter Buttons --}}
                <div class="btn-group mr-2">
                    <a href="{{ route('admin.employees.index', ['status' => 'active']) }}" 
                       class="btn btn-sm {{ $status == 'active' ? 'btn-success' : 'btn-default' }}"
                       title="Đang hoạt động">
                        <i class="fas fa-user-check"></i> Đang hoạt động
                    </a>
                    <a href="{{ route('admin.employees.index', ['status' => 'inactive']) }}" 
                       class="btn btn-sm {{ $status == 'inactive' ? 'btn-warning' : 'btn-default' }}"
                       title="Đã khóa">
                        <i class="fas fa-user-lock"></i> Đã khóa
                    </a>
                    <a href="{{ route('admin.employees.index', ['status' => 'all']) }}" 
                       class="btn btn-sm {{ $status == 'all' ? 'btn-secondary' : 'btn-default' }}"
                       title="Tất cả">
                        <i class="fas fa-list"></i> Tất cả
                    </a>
                </div>
                
                <a href="{{ route('admin.employees.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm Nhân Viên
                </a>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 10px">ID</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th style="width: 150px" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr class="{{ $employee->is_active ? '' : 'table-secondary' }}">
                                <td>{{ $employee->employeeID }}</td>
                                <td>
                                    <strong>{{ $employee->fullName }}</strong>
                                    @if (!$employee->is_active)
                                        <span class="badge badge-warning mt-1">
                                            <i class="fas fa-user-lock"></i> Đã khóa
                                        </span>
                                    @endif
                                </td>
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
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        {{-- Nút Sửa --}}
                                        <a href="{{ route('admin.employees.edit', $employee) }}" 
                                           class="btn btn-info"
                                           title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        @if ($employee->is_active)
                                            {{-- Nút KHÓA TÀI KHOẢN --}}
                                            <button type="button" 
                                                    class="btn btn-warning btn-lock-employee" 
                                                    data-employee-id="{{ $employee->employeeID }}"
                                                    data-employee-name="{{ $employee->fullName }}"
                                                    title="Khóa tài khoản"
                                                    {{-- Ngăn Admin tự khóa tài khoản của mình --}}
                                                    @if($employee->employeeID === auth('admin')->id()) disabled @endif>
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @else
                                            {{-- Nút MỞ KHÓA TÀI KHOẢN --}}
                                            <button type="button" 
                                                    class="btn btn-success btn-activate-employee" 
                                                    data-employee-id="{{ $employee->employeeID }}"
                                                    data-employee-name="{{ $employee->fullName }}"
                                                    title="Mở khóa tài khoản">
                                                <i class="fas fa-unlock"></i>
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Form ẩn để KHÓA (Soft Delete) --}}
                                    <form id="lock-form-{{ $employee->employeeID }}" 
                                          action="{{ route('admin.employees.destroy', $employee) }}" 
                                          method="POST" 
                                          style="display:none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    
                                    {{-- Form ẩn để MỞ KHÓA (Re-activate) --}}
                                    <form id="activate-form-{{ $employee->employeeID }}" 
                                          action="{{ route('admin.employees.update', $employee) }}" 
                                          method="POST" 
                                          style="display:none;">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action_reactivate" value="1">
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p>Không có nhân viên nào.</p>
                                    <a href="{{ route('admin.employees.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Tạo tài khoản nhân viên
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($employees->hasPages())
        <div class="card-footer clearfix">
            <div class="float-left">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    Hiển thị {{ $employees->firstItem() }} - {{ $employees->lastItem() }} 
                    trong tổng số <strong>{{ $employees->total() }}</strong> nhân viên
                </small>
            </div>
            <div class="float-right">
                {{ $employees->appends(['status' => $status])->links('pagination::bootstrap-5') }}
            </div>
        </div>
        @endif
    </div>
@stop

@section('css')
{{-- Thêm CSS giống trang khách hàng --}}
<style>
    .small-box h3 {
        font-size: 2.2rem;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa !important;
    }
    .table-secondary {
        opacity: 0.7; 
    }
    .table-secondary:hover {
        opacity: 1;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.3em 0.6em;
    }
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    .card-outline {
        border-top: 3px solid #007bff;
    }
</style>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    
    // ========================================================================
    // KHÓA TÀI KHOẢN (SOFT DELETE)
    // ========================================================================
    $('.btn-lock-employee').on('click', function() {
        const employeeId = $(this).data('employee-id');
        const employeeName = $(this).data('employee-name');
        
        Swal.fire({
            title: 'Xác nhận KHÓA TÀI KHOẢN?',
            html: `Bạn có chắc chắn muốn khóa tài khoản<br><strong>"${employeeName}"</strong>?<br><br>
                   <small class="text-muted">Tài khoản sẽ không thể đăng nhập.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-lock"></i> Khóa',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            customClass: {
                confirmButton: 'btn btn-warning mr-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $(`#lock-form-${employeeId}`).submit();
            }
        });
    });
    
    // ========================================================================
    // MỞ KHÓA TÀI KHOẢN (RE-ACTIVATE)
    // ========================================================================
    $('.btn-activate-employee').on('click', function() {
        const employeeId = $(this).data('employee-id');
        const employeeName = $(this).data('employee-name');
        
        Swal.fire({
            title: 'Xác nhận MỞ KHÓA?',
            html: `Bạn có chắc chắn muốn mở khóa tài khoản<br><strong>"${employeeName}"</strong>?<br><br>
                   <small class="text-muted">Tài khoản sẽ có thể đăng nhập trở lại.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-unlock"></i> Mở khóa',
            cancelButtonText: '<i class="fas fa-times"></i> Hủy',
            customClass: {
                confirmButton: 'btn btn-success mr-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $(`#activate-form-${employeeId}`).submit();
            }
        });
    });
    
    // Tự động ẩn Alert
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000); 
});
</script>
@stop
