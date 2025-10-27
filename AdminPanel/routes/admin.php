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
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\AdminDiscountController; 
// ⭐ THÊM CONTROLLER MỚI CHO SHIPPING
use App\Http\Controllers\Admin\ShippingCarrierController;
use App\Http\Controllers\Admin\ShippingRateController;
// ⭐ THÊM CONTROLLER MỚI CHO CẤU HÌNH SHIPPING
use App\Http\Controllers\Admin\ShippingConfigController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

// Đảm bảo nhóm route chính có prefix 'admin' và namespace/as 'admin.'
Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {

    // 1. Routes Đăng nhập (dùng Guard 'admin')
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');


    // 2. Khu vực được bảo vệ (Chỉ truy cập khi đã đăng nhập Admin)
    Route::middleware(['web', 'auth:admin'])->group(function () {

        // Dashboard (Tất cả nhân viên đã đăng nhập)
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // =========================================================================
        // 2.A. CHAT REAL-TIME (Tất cả nhân viên được quyền truy cập)
        // =========================================================================

        // Giao diện chính cho Chat (View)
        Route::get('chat', [ChatController::class, 'index'])->name('chat.index');

        // API: Lấy danh sách nhân viên (trừ chính mình)
        Route::get('chat/employees', [ChatController::class, 'getEmployees'])->name('chat.employees');

        // API: Gửi tin nhắn (Cần khớp với tên route trong JS: admin.chat.send)
        Route::post('chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');

        // API: Lấy lịch sử chat (Cần khớp với cú pháp trong Controller: getHistory($receiverId))
        Route::get('chat/history/{receiverId}', [ChatController::class, 'getHistory'])->name('chat.history');


        // =========================================================================
        // 2.1. ADMIN ONLY: Cấu hình Hệ thống (employees, brands, categories, SHIPPING)
        // =========================================================================
        Route::middleware('can:admin')->group(function () {
            // Tài khoản NV
            Route::resource('employees', EmployeeController::class)->except(['show']);
            // Thương hiệu & Danh mục SP
            Route::resource('brands', BrandController::class);
            Route::resource('categories', CategoryController::class);

            // ⭐ THÊM ROUTES QUẢN LÝ SHIPPING CARRIERS VÀ RATES ⭐
            // Đơn vị Vận chuyển (Carriers)
            Route::resource('carriers', ShippingCarrierController::class)->parameters([
                'carriers' => 'carrier', 
            ]);
            // Mức phí Vận chuyển (Rates)
            Route::resource('rates', ShippingRateController::class)->parameters([
                'rates' => 'rate', 
            ]);
            
            // ⭐ ĐỊNH NGHĨA ROUTES CẤU HÌNH SHIP (FREE SHIP THRESHOLD) ⭐
            Route::prefix('shipping')->as('shipping.')->controller(ShippingConfigController::class)->group(function () {
                // GET /admin/shipping/config -> Hiển thị form
                Route::get('config', 'edit')->name('config.edit'); 
                // PUT /admin/shipping/config -> Cập nhật giá trị
                Route::put('config', 'update')->name('config.update');
            });
        });

        // =========================================================================
        // 2.2. STAFF: Quản lý Kho hàng & Đánh giá (Admin/Staff)
        // =========================================================================
        Route::middleware('can:staff')->group(function () {
            // Sản phẩm & Tồn kho (CRUD Sản phẩm)
            Route::resource('products', ProductController::class);
            
            // FIX 403: Đặt Route DELETE IMAGE ở đây cho phép Staff và Admin xóa ảnh
            Route::delete('products/{product}/images/{imageID}', [ProductController::class, 'deleteImage'])->name('products.delete.image');
            
            // Quản lý Đánh giá (Xem, duyệt, ẩn/hiện)
            Route::resource('reviews', ReviewController::class)->except(['create', 'store']);
        });

        // =========================================================================
        // 2.3. MARKETING: Khuyến mãi & Banner (Admin/Marketing)
        // =========================================================================
        Route::middleware('can:marketing')->group(function () {
            // Mã giảm giá (Vouchers)
            Route::resource('vouchers', VoucherController::class);
            
            // Slider/Banner
            Route::resource('sliders', SliderController::class);

            // === [QUẢN LÝ CHƯƠNG TRÌNH GIẢM GIÁ SẢN PHẨM MỚI] ===
            
            // 1. ROUTE WEB (GET /admin/product-discounts) -> Trả về View HTML
            Route::get('product-discounts', [AdminDiscountController::class, 'index'])->name('product-discounts.index');
            
            // 2. ROUTE API (LẤY DANH SÁCH JSON cho AJAX/Datatable)
            Route::get('product-discounts/api-list', [AdminDiscountController::class, 'apiIndex'])->name('product-discounts.apiIndex'); 

            // 3. ROUTE RESOURCE CÒN LẠI (POST, CREATE, EDIT, UPDATE, DELETE)
            Route::resource('product-discounts', AdminDiscountController::class)->except(['index', 'show']);

            // 4. ROUTE API SHOW (Nếu cần hiển thị chi tiết bằng AJAX)
            Route::get('product-discounts/{id}/show-api', [AdminDiscountController::class, 'show'])->name('product-discounts.show.api');
            
            // 5. Route riêng để Tắt/Bật nhanh chương trình sale
            Route::put('product-discounts/{id}/toggle-active', [AdminDiscountController::class, 'toggleActive'])->name('product-discounts.toggleActive');
        });

        // =========================================================================
        // 2.4. ĐƠN HÀNG (Tất cả nhân viên được xem)
        // =========================================================================
        Route::resource('orders', OrderController::class);
    });
});