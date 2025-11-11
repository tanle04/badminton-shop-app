@extends('adminlte::page')

@section('title', 'Quản lý Khách hàng')

@section('content_header')
  <div class="row">
    <div class="col-sm-6">
      <h1>
        <i class="fas fa-users"></i> Quản lý Khách hàng
      </h1>
    </div>
    <div class="col-sm-6">
      <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Khách hàng</li>
      </ol>
    </div>
  </div>
@stop

@section('content')
    {{-- ⭐ SỬA LỖI: Thêm row và col-12 để bao bọc toàn bộ nội dung --}}
    <div class="row">
        <div class="col-12">

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
            {{-- 💡 LƯU Ý: Controller của bạn cần truyền 3 biến này: --}}
            {{-- $totalCustomerCount, $activeCustomerCount, $inactiveCustomerCount --}}
            <div class="row mb-3">
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>{{ $totalCustomerCount ?? 0 }}</h3>
                            <p>Tổng tài khoản</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <a href="{{ route('admin.customers.index', ['status' => 'all']) }}" class="small-box-footer">
                            Xem tất cả <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>{{ $activeCustomerCount ?? 0 }}</h3>
                            <p>Đang hoạt động</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <a href="{{ route('admin.customers.index', ['status' => 'active']) }}" class="small-box-footer">
                            Chi tiết <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>{{ $inactiveCustomerCount ?? 0 }}</h3>
                            <p>Đã khóa</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-lock"></i>
                        </div>
                        <a href="{{ route('admin.customers.index', ['status' => 'inactive']) }}" class="small-box-footer">
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
                            Khách hàng Đang hoạt động
                        @elseif($status == 'inactive')
                            Khách hàng Đã khóa
                        @else
                            Tất cả Khách hàng
                        @endif
                    </h3>
                    <div class="card-tools">
                        {{-- Filter Buttons --}}
                        <div class="btn-group mr-2">
                            <a href="{{ route('admin.customers.index', ['status' => 'active']) }}" 
                               class="btn btn-sm {{ $status == 'active' ? 'btn-success' : 'btn-default' }}"
                               title="Đang hoạt động">
                                <i class="fas fa-user-check"></i> Đang hoạt động
                            </a>
                            <a href="{{ route('admin.customers.index', ['status' => 'inactive']) }}" 
                               class="btn btn-sm {{ $status == 'inactive' ? 'btn-warning' : 'btn-default' }}"
                               title="Đã khóa">
                                <i class="fas fa-user-lock"></i> Đã khóa
                            </a>
                            <a href="{{ route('admin.customers.index', ['status' => 'all']) }}" 
                               class="btn btn-sm {{ $status == 'all' ? 'btn-secondary' : 'btn-default' }}"
                               title="Tất cả">
                                <i class="fas fa-list"></i> Tất cả
                            </a>
                        </div>
                        
                        {{-- Add Button --}}
                        <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Thêm Khách Hàng
                        </a>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 10px">ID</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Số điện thoại</th>
                                    <th>Xác thực Email</th>
                                    <th>Ngày tạo</th>
                                    <th style="width: 150px" class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                    {{-- Thêm class 'table-secondary' nếu tài khoản bị khóa --}}
                                    <tr class="{{ $customer->is_active ? '' : 'table-secondary' }}">
                                        <td>{{ $customer->customerID }}</td>
                                        <td>
                                            <strong>{{ $customer->fullName }}</strong>
                                            {{-- Hiển thị badge nếu tài khoản bị khóa --}}
                                            @if (!$customer->is_active)
                                                <span class="badge badge-warning mt-1">
                                                    <i class="fas fa-user-lock"></i> Đã khóa
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $customer->email }}</td>
                                        <td>{{ $customer->phone }}</td>
                                        <td>
                                            @if ($customer->isEmailVerified)
                                                <span class="badge bg-success">Đã xác thực</span>
                                            @else
                                                <span class="badge bg-warning">Chưa</span>
                                            @endif
                                        </td>
                                        <td>{{ $customer->createdDate ? $customer->createdDate->format('d/m/Y H:i') : 'N/A' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                
                                                {{-- Nút Xem (Show) --}}
                                                <a href="{{ route('admin.customers.show', $customer) }}" 
                                                   class="btn btn-default text-info"
                                                   title="Chi tiết">
                                                    <i class="fa fa-eye"></i>
                                                </a>

                                                {{-- Nút Sửa --}}
                                                <a href="{{ route('admin.customers.edit', $customer) }}" 
                                                   class="btn btn-info"
                                                   title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                @if ($customer->is_active)
                                                    {{-- Nút KHÓA TÀI KHOẢN --}}
                                                    <button type="button" 
                                                            class="btn btn-warning btn-lock-customer" 
                                                            data-customer-id="{{ $customer->customerID }}"
                                                            data-customer-name="{{ $customer->fullName }}"
                                                            title="Khóa tài khoản">
                                                        <i class="fas fa-lock"></i>
                                                    </button>
                                                @else
                                                    {{-- Nút MỞ KHÓA TÀI KHOẢN --}}
                                                    <button type="button" 
                                                            class="btn btn-success btn-activate-customer" 
                                                            data-customer-id="{{ $customer->customerID }}"
                                                            data-customer-name="{{ $customer->fullName }}"
                                                            title="Mở khóa tài khoản">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
                                                @endif
                                            </div>

                                            {{-- Form ẩn để KHÓA (Soft Delete) --}}
                                            <form id="lock-form-{{ $customer->customerID }}" 
                                                  action="{{ route('admin.customers.destroy', $customer) }}" 
                                                  method="POST" 
                                                  style="display:none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            
                                            {{-- Form ẩn để MỞ KHÓA (Re-activate) --}}
                                            <form id="activate-form-{{ $customer->customerID }}" 
                                                  action="{{ route('admin.customers.update', $customer) }}" 
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
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>Không có khách hàng nào</p>
                                            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Tạo khách hàng
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                @if($customers->hasPages())
                <div class="card-footer clearfix">
                    <div class="float-left">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Hiển thị {{ $customers->firstItem() }} - {{ $customers->lastItem() }} 
                            trong tổng số <strong>{{ $customers->total() }}</strong> khách hàng
                        </small>
                    </div>
                    <div class="float-right">
                        {{-- Giữ lại appends để việc lọc theo status hoạt động khi chuyển trang --}}
                        {{ $customers->appends(['status' => $status])->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif
            </div>

        </div>
    </div>
@stop

@section('css')
{{-- Thêm CSS giống trang sản phẩm để giao diện nhất quán --}}
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
{{-- SweetAlert2 cho các popup xác nhận --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
  console.log('✅ Customers index page ready');
  
  // ========================================================================
  // KHÓA TÀI KHOẢN (SOFT DELETE)
  // ========================================================================
  $('.btn-lock-customer').on('click', function() {
    const customerId = $(this).data('customer-id');
    const customerName = $(this).data('customer-name');
    
    console.log('🔒 Locking customer:', customerId, customerName);
    
    Swal.fire({
      title: 'Xác nhận KHÓA TÀI KHOẢN?',
      html: `Bạn có chắc chắn muốn khóa tài khoản<br><strong>"${customerName}"</strong>?<br><br>
         <small class="text-muted">Tài khoản sẽ không thể đăng nhập và bị ẩn.</small>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ffc107', // Màu vàng
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
        console.log('✅ Lock confirmed, submitting form');
        $(`#lock-form-${customerId}`).submit();
      }
    });
  });
  
  // ========================================================================
  // MỞ KHÓA TÀI KHOẢN (RE-ACTIVATE)
  // ========================================================================
  $('.btn-activate-customer').on('click', function() {
    const customerId = $(this).data('customer-id');
    const customerName = $(this).data('customer-name');
    
    console.log('✅ Activating customer:', customerId, customerName);
    
    Swal.fire({
      title: 'Xác nhận MỞ KHÓA?',
      html: `Bạn có chắc chắn muốn mở khóa tài khoản<br><strong>"${customerName}"</strong>?<br><br>
         <small class="text-muted">Tài khoản sẽ có thể đăng nhập trở lại.</small>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745', // Màu xanh
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
        console.log('✅ Activation confirmed, submitting form');
        $(`#activate-form-${customerId}`).submit();
      }
    });
  });
  
  // ========================================================================
  // TỰ ĐỘNG ẨN ALERT
  // ========================================================================
  setTimeout(function() {
    $('.alert').fadeOut('slow');
  }, 5000); // 5 giây
});
</script>
@stop
