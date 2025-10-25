<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SliderController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DashboardController;

/* --- ADMIN ROUTES (Phân quyền bằng Laravel Gates) --- */

Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {

    // 1. Routes Đăng nhập (dùng Guard 'admin')
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');


    // 2. Khu vực được bảo vệ (Chỉ truy cập khi đã đăng nhập Admin)
    Route::middleware(['auth:admin'])->group(function () {

        // Dashboard (Tất cả nhân viên đã đăng nhập)


        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // =========================================================================
        // 2.1. ADMIN ONLY: Cấu hình Hệ thống (employees, brands, categories)
        // Yêu cầu Gate::allows('admin')
        // =========================================================================
        Route::middleware('can:admin')->group(function () {
            // Tài khoản NV
            Route::resource('employees', EmployeeController::class)->except(['show']);
            // Thương hiệu & Danh mục SP
            Route::resource('brands', BrandController::class);
            Route::resource('categories', CategoryController::class);
            // Thêm route này vào nhóm middleware: Route::middleware('can:staff')->group(function () { ...
            Route::delete('products/{product}/images/{imageID}', [App\Http\Controllers\Admin\ProductController::class, 'deleteImage'])->name('admin.products.delete.image');
        });

        // =========================================================================
        // 2.2. STAFF: Quản lý Kho hàng & Đánh giá (Admin/Staff)
        // Yêu cầu Gate::allows('staff')
        // =========================================================================
        Route::middleware('can:staff')->group(function () {
            // Sản phẩm & Tồn kho (CRUD Sản phẩm)
            Route::resource('products', ProductController::class);
            // Quản lý Đánh giá (Xem, duyệt, ẩn/hiện)
            Route::resource('reviews', ReviewController::class)->except(['create', 'store']);
        });

        // =========================================================================
        // 2.3. MARKETING: Khuyến mãi & Banner (Admin/Marketing)
        // Yêu cầu Gate::allows('marketing')
        // =========================================================================
        Route::middleware('can:marketing')->group(function () {
            // Mã giảm giá (Vouchers)
            Route::resource('vouchers', VoucherController::class);
            // Slider/Banner
            Route::resource('sliders', SliderController::class);
        });

        // =========================================================================
        // 2.4. ĐƠN HÀNG (Tất cả nhân viên được xem)
        // Nằm trong auth:admin, KHÔNG cần middleware 'can' cứng.
        // Logic thay đổi trạng thái sẽ được xử lý trong OrderController.
        // =========================================================================
        Route::resource('orders', OrderController::class);
    });
});

/* -------------------- */