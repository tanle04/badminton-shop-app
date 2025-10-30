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

use App\Http\Controllers\Admin\ShippingCarrierController;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Http\Controllers\Admin\ShippingConfigController;

// ⭐ KHAI BÁO CONTROLLER CHO THUỘC TÍNH SẢN PHẨM ⭐
use App\Http\Controllers\Admin\ProductAttributeController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

// Đảm bảo nhóm route chính có prefix 'admin' và namespace/as 'admin.'
Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {

    // =====================================================================
    // 1. ROUTES ĐĂNG NHẬP (KHÔNG CẦN AUTH)
    // =====================================================================
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');


    // =====================================================================
    // 2. KHU VỰC ĐƯỢC BẢO VỆ (CHỈ TRUY CẬP KHI ĐÃ ĐĂNG NHẬP)
    // =====================================================================
    Route::middleware(['web', 'auth:admin'])->group(function () {

        // Dashboard (Tất cả nhân viên đã đăng nhập)
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // =================================================================
        // 2.A. CHAT REAL-TIME (TẤT CẢ NHÂN VIÊN)
        // =================================================================
        Route::prefix('chat')->name('chat.')->controller(ChatController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/employees', 'getEmployees')->name('employees');
            Route::post('/chat/send', [ChatController::class, 'sendMessage'])
                ->name('admin.chat.send');
            Route::post('/send', 'sendMessage')->name('send');
            Route::get('/history/{receiverId}', 'getHistory')->name('history');
            Route::get('/chat/unread-count', [ChatController::class, 'getUnreadCount']);
            Route::post('/chat/mark-read/{employeeId}', [ChatController::class, 'markAsRead']);
        });

        // =================================================================
        // 2.1. ADMIN ONLY: Cấu hình Hệ thống
        // =================================================================
        Route::middleware('can:admin')->group(function () {
            // Tài khoản Nhân viên
            Route::resource('employees', EmployeeController::class)->except(['show']);

            // Thương hiệu & Danh mục Sản phẩm
            Route::resource('brands', BrandController::class);
            Route::resource('categories', CategoryController::class);

            // Đơn vị Vận chuyển (Carriers)
            Route::resource('carriers', ShippingCarrierController::class)->parameters([
                'carriers' => 'carrier',
            ]);

            // Mức phí Vận chuyển (Rates)
            Route::resource('rates', ShippingRateController::class)->parameters([
                'rates' => 'rate',
            ]);

            // Cấu hình Vận chuyển (Free Ship Threshold)
            Route::prefix('shipping')->name('shipping.')->controller(ShippingConfigController::class)->group(function () {
                Route::get('config', 'edit')->name('config.edit');
                Route::put('config', 'update')->name('config.update');
            });
        });

        // =================================================================
        // 2.2. STAFF: Quản lý Kho hàng & Đánh giá (Admin/Staff)
        // =================================================================
        Route::middleware('can:staff')->group(function () {

            // ---------------------------------------------------------
            // QUẢN LÝ SẢN PHẨM (PRODUCTS)
            // ---------------------------------------------------------
            Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
                // CRUD cơ bản
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{product}/edit', 'edit')->name('edit');
                Route::put('/{product}', 'update')->name('update');
                Route::delete('/{product}', 'destroy')->name('destroy');

                // ⭐ API: Lấy thuộc tính theo category (Ajax) - ROUTE MỚI
                Route::get('/category/{categoryID}/attributes', 'getAttributesByCategory')
                    ->name('category.attributes');

                // Xóa ảnh sản phẩm (Ajax)
                Route::delete('/{product}/images/{imageID}', 'deleteImage')
                    ->name('delete.image');
            });

            // ---------------------------------------------------------
            // QUẢN LÝ ĐÁNH GIÁ (REVIEWS)
            // ---------------------------------------------------------
            Route::resource('reviews', ReviewController::class)->except(['create', 'store']);

            // ---------------------------------------------------------
            // ⭐ QUẢN LÝ THUỘC TÍNH SẢN PHẨM (ATTRIBUTES)
            // ---------------------------------------------------------
            Route::prefix('attributes')->name('attributes.')->controller(ProductAttributeController::class)->group(function () {

                // CRUD Attributes (Tên thuộc tính: Size, Grip, Trọng lượng...)
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{attribute}/edit', 'edit')->name('edit');
                Route::put('/{attribute}', 'update')->name('update');
                Route::delete('/{attribute}', 'destroy')->name('destroy');

                // Gán Danh mục cho Attributes
                Route::get('/{attributeID}/categories', 'getAssignedCategories')
                    ->name('categories.get');
                Route::post('/{attributeID}/assign-categories', 'assignCategories')
                    ->name('categories.assign');

                // Quản lý Values (Giá trị: M, L, XL, G5, G6...)
                Route::prefix('{attributeID}/values')->name('values.')->group(function () {
                    Route::get('/', 'showValues')->name('index');
                    Route::post('/', 'storeValue')->name('store');
                    Route::put('/{valueID}', 'updateValue')->name('update');
                    Route::delete('/{valueID}', 'destroyValue')->name('destroy');
                });
            });
        });

        // =================================================================
        // 2.3. MARKETING: Khuyến mãi & Banner (Admin/Marketing)
        // =================================================================
        Route::middleware('can:marketing')->group(function () {

            // Mã giảm giá (Vouchers)
            // ============================================================================
            // WEB ROUTES (Hiển thị views)
            // ============================================================================
            Route::resource('vouchers', VoucherController::class);

            // ============================================================================
            // API ROUTES (Cho AJAX)
            // ============================================================================

            // GET: Lấy danh sách vouchers với search & filter
            Route::get('vouchers-api/list', [VoucherController::class, 'apiIndex'])
                ->name('vouchers.apiIndex');

            // GET: Lấy thống kê vouchers
            Route::get('vouchers-api/stats', [VoucherController::class, 'apiStats'])
                ->name('vouchers.apiStats');

            // PUT: Bật/tắt voucher
            Route::put('vouchers/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])
                ->name('vouchers.toggleActive');
            // Slider/Banner
            // Resource routes (CRUD cơ bản)
            Route::resource('sliders', SliderController::class);

            // API routes (cho AJAX)
            Route::get('sliders-api/list', [SliderController::class, 'apiIndex'])
                ->name('sliders.apiIndex');

            // Cập nhật thứ tự hiển thị (drag & drop)
            Route::post('sliders/update-order', [SliderController::class, 'updateOrder'])
                ->name('sliders.updateOrder');

            // Toggle status (active/inactive)
            Route::post('sliders/{slider}/toggle-status', [SliderController::class, 'toggleStatus'])
                ->name('sliders.toggleStatus');

            // Chương trình Giảm giá Sản phẩm
            Route::prefix('product-discounts')->name('product-discounts.')->controller(AdminDiscountController::class)->group(function () {
                // View chính
                Route::get('/', 'index')->name('index');

                // API cho DataTable
                Route::get('/api-list', 'apiIndex')->name('apiIndex');

                // CRUD
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');

                // API show detail
                Route::get('/{id}/show-api', 'show')->name('show.api');

                // Toggle active/inactive
                Route::put('/{id}/toggle-active', 'toggleActive')->name('toggleActive');
            });
        });

        // =================================================================
        // 2.4. ĐƠN HÀNG (TẤT CẢ NHÂN VIÊN)
        // =================================================================
        Route::resource('orders', OrderController::class);
    });
});
