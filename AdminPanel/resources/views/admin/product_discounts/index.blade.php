{{-- resources/views/admin/product_discounts/index.blade.php --}}
@extends('adminlte::page') 
{{-- Sử dụng layout chính xác như file Vouchers của bạn --}}

@section('title', 'Quản lý Chương trình Giảm giá Sản phẩm')

@section('content_header')
    <h1>Chương trình Giảm giá Sản phẩm</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Danh sách Chương trình Sale</h3>
        <div class="card-tools">
            <a href="{{ route('admin.product-discounts.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tạo Sale mới
            </a>
        </div>
    </div>
    
    {{-- PHẦN THÂN CARD NÀY SẼ DÙNG AJAX ĐỂ LOAD DATA --}}
    <div class="card-body p-0">
        <table id="discountsTable" class="table table-hover">
            <thead>
                <tr>
                    <th style="width: 5%">ID</th>
                    <th>Tên chương trình</th>
                    <th>Giá trị/Loại</th>
                    <th>Áp dụng cho</th>
                    <th>Thời gian</th>
                    <th style="width: 10%">Trạng thái</th>
                    <th style="width: 15%">Hành động</th>
                </tr>
            </thead>
            <tbody>
                {{-- Dữ liệu sẽ được load và render tại đây bằng JavaScript/AJAX --}}
                {{-- Dùng Blade để hiển thị Loading Spinner ban đầu --}}
                <tr>
                    <td colspan="7" class="text-center">Đang tải dữ liệu...</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="card-footer clearfix">
        {{-- Footer cho phân trang, sẽ được cập nhật bởi JS/AJAX --}}
        <div id="pagination-links"></div>
    </div>
</div>
@stop

{{-- SỬ DỤNG @section('js') cho scripts trong AdminLTE --}}
@section('js')
<script>
$(document).ready(function() {
    // Lấy CSRF token từ meta tag (Rất quan trọng cho POST/PUT/DELETE)
    // Đảm bảo bạn có <meta name="csrf-token" content="{{ csrf_token() }}"> trong master layout
    const csrfToken = $('meta[name="csrf-token"]').attr('content'); 
    
    // Hàm format tiền tệ
    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
    };

    // Hàm render HTML cho bảng
    function renderDiscounts(data) {
        let rows = '';
        data.forEach(discount => {
            
            // Tính toán loại và giá trị giảm giá để hiển thị
            let valueDisplay = '';
            if (discount.discountType === 'percentage') {
                valueDisplay = `${discount.discountValue}%`;
                if (discount.maxDiscountAmount) {
                    valueDisplay += ` (Max: ${formatCurrency(discount.maxDiscountAmount)})`;
                }
            } else {
                valueDisplay = formatCurrency(discount.discountValue);
            }
            
            // Xử lý trạng thái (Active/Inactive/Expired)
            const today = new Date();
            const endDate = new Date(discount.endDate);
            let statusBadge = '';
            let isActive = discount.isActive;
            
            if (!isActive) {
                statusBadge = '<span class="badge badge-danger">Ngưng hoạt động</span>';
            } else if (endDate < today) {
                 statusBadge = '<span class="badge badge-warning">Đã hết hạn</span>';
            } else {
                 statusBadge = '<span class="badge badge-success">Đang hoạt động</span>';
            }


            rows += `
            <tr data-id="${discount.discountID}">
                <td>${discount.discountID}</td>
                <td><strong>${discount.discountName}</strong></td>
                <td>${valueDisplay}</td>
                <td>${discount.appliedToType.toUpperCase()} ID: ${discount.appliedToID}</td>
                <td>${new Date(discount.startDate).toLocaleDateString()} - ${new Date(discount.endDate).toLocaleDateString()}</td>
                <td>${statusBadge}</td>
                <td>
                    <a href="{{ route('admin.product-discounts.edit', ['product_discount' => 'TEMP_ID']) }}" 
                       class="btn btn-info btn-xs btn-edit" data-id="${discount.discountID}">Sửa</a>
                    
                    <button class="btn btn-danger btn-xs btn-delete" data-id="${discount.discountID}">Xóa</button>
                    
                    <button class="btn btn-sm ${isActive ? 'btn-secondary' : 'btn-success'} btn-toggle" 
                            data-id="${discount.discountID}" data-status="${isActive ? 'active' : 'inactive'}">
                            ${isActive ? 'Tắt' : 'Bật'}
                    </button>
                </td>
            </tr>
            `.replace(/TEMP_ID/g, discount.discountID);
        });
        
        $('#discountsTable tbody').html(rows);
    }
    
    // Hàm load dữ liệu từ API
    function loadDiscounts() {
        $.ajax({
            url: '{{ route("admin.product-discounts.apiIndex") }}', 
            method: 'GET',
            success: function(response) {
                renderDiscounts(response.data);
                // Bạn có thể dùng response.links để cập nhật #pagination-links
            },
            error: function(xhr) {
                $('#discountsTable tbody').html('<tr><td colspan="7" class="text-center text-danger">Lỗi tải dữ liệu. Vui lòng kiểm tra API.</td></tr>');
                console.error("Lỗi khi load chương trình giảm giá", xhr);
            }
        });
    }

    loadDiscounts();

    // ----------------------------------------------------
    // LOGIC XỬ LÝ SỰ KIỆN DELETE/TOGGLE (Dùng AJAX)
    // ----------------------------------------------------

    // 1. Logic Xóa
    $(document).on('click', '.btn-delete', function() {
        const discountId = $(this).data('id');
        if (confirm('Bạn có chắc chắn muốn xóa chương trình giảm giá này?')) {
            $.ajax({
                url: `/admin/product-discounts/${discountId}`, // Dùng URL resource
                method: 'POST', // Laravel nhận DELETE qua POST + method field
                data: {
                    _method: 'DELETE',
                    _token: csrfToken
                },
                success: function(response) {
                    alert('Chương trình giảm giá đã được xóa.');
                    loadDiscounts(); // Tải lại bảng
                },
                error: function(xhr) {
                    alert('Lỗi xóa chương trình giảm giá.');
                }
            });
        }
    });

    // 2. Logic Tắt/Bật
    $(document).on('click', '.btn-toggle', function() {
        const discountId = $(this).data('id');
        $.ajax({
            url: '{{ route("admin.product-discounts.toggleActive", ["id" => "TEMP_ID"]) }}'.replace("TEMP_ID", discountId),
            method: 'PUT',
            data: { _token: csrfToken }, // Truyền token
            headers: {
                 'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                 alert('Trạng thái đã được cập nhật thành: ' + (response.isActive ? 'HOẠT ĐỘNG' : 'NGƯNG HOẠT ĐỘNG'));
                 loadDiscounts(); // Tải lại bảng
            },
            error: function(xhr) {
                 alert('Lỗi cập nhật trạng thái.');
            }
        });
    });
});
</script>
@stop