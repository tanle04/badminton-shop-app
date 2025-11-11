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
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\ShippingCarrierController;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Http\Controllers\Admin\ShippingConfigController;
use App\Http\Controllers\Admin\ProductAttributeController;
use App\Http\Controllers\Admin\CategoryAttributeController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES - Phân quyền theo nghiệp vụ thực tế
|--------------------------------------------------------------------------
*/

Route::group(['as' => 'admin.'], function () {

    // =====================================================================
    // 1. AUTHENTICATION (Không cần auth)
    // =====================================================================
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    // =====================================================================
    // 2. PROTECTED ROUTES (Cần đăng nhập)
    // =====================================================================
    Route::middleware(['web', 'auth:admin'])->group(function () {

        // =================================================================
        // 2.1. COMMON - TẤT CẢ ROLES (Dashboard, Chat, Support)
        // =================================================================
        
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Chat Nội Bộ
        Route::prefix('chat')->name('chat.')->controller(ChatController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/employees', 'getEmployees')->name('employees');
            Route::post('/send', 'sendMessage')->name('send');
            Route::get('/history/{receiverId}', 'getHistory')->name('history');
            Route::get('/unread-count', 'getUnreadCount')->name('unread-count');
            Route::post('/mark-read/{employeeId}', 'markAsRead')->name('mark-read');
        });

        // Hỗ trợ Khách hàng
        Route::prefix('support-chat')->name('support-chat.')->controller(\App\Http\Controllers\Admin\SupportChatController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/conversations', 'getConversations')->name('conversations');
            Route::get('/conversation/{conversationId}/history', 'getConversationHistory')->name('conversation.history');
            Route::post('/send', 'sendMessage')->name('send');
            Route::post('/conversation/{conversationId}/assign', 'assignConversation')->name('conversation.assign');
            Route::post('/conversation/{conversationId}/close', 'closeConversation')->name('conversation.close');
            Route::post('/conversation/{conversationId}/mark-read', 'markAsRead')->name('conversation.markRead');
            Route::get('/stats', 'getStats')->name('stats');
        });

        // =================================================================
        // 2.2. ORDERS - TẤT CẢ ROLES (Xem và xử lý đơn hàng)
        // =================================================================
        Route::resource('orders', OrderController::class);
        Route::put('orders/{order}/refund/approve', [OrderController::class, 'approveRefund'])
            ->name('orders.refund.approve');
        Route::put('orders/{order}/refund/reject', [OrderController::class, 'rejectRefund'])
            ->name('orders.refund.reject');

        // =================================================================
        // 2.3. CUSTOMERS - TẤT CẢ ROLES (Xem thông tin khách hàng)
        // =================================================================
        // Staff: Xem để hỗ trợ
        // Marketing: Xem để phân tích
        // Admin: Quản lý đầy đủ
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        
        // Chỉ Admin mới được edit/delete
        Route::middleware('can:admin')->group(function () {
            Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
            Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
        });

        // =================================================================
        // 2.4. STAFF AREA - Quản lý Sản phẩm & Đánh giá
        // =================================================================
        Route::middleware('can:staff')->group(function () {
            
            // Sản phẩm
            Route::prefix('products')->name('products.')->controller(ProductController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{product}/edit', 'edit')->name('edit');
                Route::put('/{product}', 'update')->name('update');
                Route::delete('/{product}', 'destroy')->name('destroy');
                Route::get('/category/{categoryID}/attributes', 'getAttributesByCategory')
                    ->name('category.attributes'); 
                Route::delete('/{product}/images/{imageID}', 'deleteImage')
                    ->name('delete.image');
            });

            // Đánh giá
            Route::resource('reviews', ReviewController::class)->except(['create', 'store']);

            // Thuộc tính sản phẩm (Chỉ Admin & Staff)
            Route::prefix('attributes')->name('attributes.')->controller(ProductAttributeController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{attribute}/edit', 'edit')->name('edit');
                Route::put('/{attribute}', 'update')->name('update');
                Route::delete('/{attribute}', 'destroy')->name('destroy');
                Route::get('/{attributeID}/categories', 'getAssignedCategories')
                    ->name('categories.get');
                Route::post('/{attributeID}/assign-categories', 'assignCategories')
                    ->name('categories.assign');
                Route::prefix('{attributeID}/values')->name('values.')->group(function () {
                    Route::get('/', 'showValues')->name('index');
                    Route::post('/', 'storeValue')->name('store');
                    Route::put('/{valueID}', 'updateValue')->name('update');
                    Route::delete('/{valueID}', 'destroyValue')->name('destroy');
                });
            });
        });

        // =================================================================
        // 2.5. MARKETING AREA - Khuyến mãi & Nội dung
        // =================================================================
        Route::middleware('can:marketing')->group(function () {
            
            // Voucher
            Route::resource('vouchers', VoucherController::class);
            Route::get('vouchers-api/list', [VoucherController::class, 'apiIndex'])
                ->name('vouchers.apiIndex');
            Route::get('vouchers-api/stats', [VoucherController::class, 'apiStats'])
                ->name('vouchers.apiStats');
            Route::put('vouchers/{voucher}/toggle-active', [VoucherController::class, 'toggleActive'])
                ->name('vouchers.toggleActive');

            // Slider/Banner
            Route::resource('sliders', SliderController::class);
            Route::get('sliders-api/list', [SliderController::class, 'apiIndex'])
                ->name('sliders.apiIndex');
            Route::post('sliders/update-order', [SliderController::class, 'updateOrder'])
                ->name('sliders.updateOrder');
            Route::post('sliders/{slider}/toggle-status', [SliderController::class, 'toggleStatus'])
                ->name('sliders.toggleStatus');

            // Chương trình giảm giá sản phẩm
            Route::prefix('product-discounts')->name('product-discounts.')->controller(AdminDiscountController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/api-list', 'apiIndex')->name('apiIndex');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::get('/{id}/show-api', 'show')->name('show.api');
                Route::put('/{id}/toggle-active', 'toggleActive')->name('toggleActive');
                Route::get('/api/get-product-variants/{id}', 'getProductVariants')->name('get-product-variants');
                Route::get('/api/get-min-price', 'getMinPrice')->name('get-min-price');
            });
        });

        // =================================================================
        // 2.6. ADMIN ONLY - Cấu hình Hệ thống
        // =================================================================
        Route::middleware('can:admin')->group(function () {
            
            // Quản lý nhân viên
            Route::resource('employees', EmployeeController::class)->except(['show']);
            
            // Thương hiệu
            Route::resource('brands', BrandController::class);
            
            // Danh mục sản phẩm
            Route::resource('categories', CategoryController::class);
            Route::get('categories/{category}/attributes', [CategoryAttributeController::class, 'index'])
                 ->name('categories.attributes.index');
            Route::post('categories/{category}/attributes', [CategoryAttributeController::class, 'store'])
                 ->name('categories.attributes.store');

            // Vận chuyển
            Route::resource('carriers', ShippingCarrierController::class)->parameters([
                'carriers' => 'carrier',
            ]);

            Route::resource('rates', ShippingRateController::class)->parameters([
                'rates' => 'rate',
            ]);

            Route::prefix('shipping')->name('shipping.')->controller(ShippingConfigController::class)->group(function () {
                Route::get('config', 'edit')->name('config.edit');
                Route::put('config', 'update')->name('config.update');
            });
        });
    });
});