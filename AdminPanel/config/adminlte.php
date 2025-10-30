<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    */

    'title' => 'Badminton Shop Admin',
    'title_prefix' => '',
    'title_postfix' => ' | Admin',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    */

    'use_ico_only' => false,
    'use_full_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Logo
    |--------------------------------------------------------------------------
    */

    'logo' => '<b>Admin</b>LTE',
    'logo_img' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
    'logo_img_class' => 'brand-image opacity-8',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'Admin Logo',

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_custom_sidebar' => null,

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */

    'login_url' => 'admin/login',
    'logout_url' => 'admin/logout', // ĐÚNG: AdminLTE sẽ dùng URL này để tạo POST request
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Mix (Vô hiệu hóa vì bạn đang dùng Vite)
    |--------------------------------------------------------------------------
    */

    'enabled_laravel_mix' => false,
    'laravel_mix_css_path' => 'css/app.css',
    'laravel_mix_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    */

    'menu' => [
        // Menu quan trọng: Dashboard (Tất cả nhân viên đã đăng nhập)
        [
            'text' => 'Dashboard',
            'url'  => 'admin/dashboard',
            'icon' => 'fas fa-fw fa-home',
            'active' => ['admin/dashboard'],
        ],

        // =========================================================================
        // THÊM MỤC CHAT VÀO MENU
        // =========================================================================
        [
            'text' => 'Chat Nội Bộ',
            'url'  => 'admin/chat',
            'icon' => 'fas fa-fw fa-comments',
            'active' => ['admin/chat'],
        ],
        // =========================================================================

        ['header' => 'QUẢN LÝ NGHIỆP VỤ', 'can' => 'auth:admin'], // Tất cả đều thấy header này

        // ĐƠN HÀNG (Tất cả nhân viên xem)
        [
            'text' => 'Đơn hàng',
            'url'  => 'admin/orders',
            'icon' => 'fas fa-fw fa-shopping-cart',
            // KHÔNG CẦN 'can' vì tất cả nhân viên đều xem được index
        ],

        // KHO HÀNG & ĐÁNH GIÁ (Admin & Staff)
        [
            'text' => 'Sản phẩm & Tồn kho',
            'url'  => 'admin/products',
            'icon' => 'fas fa-fw fa-box',
            'can'  => 'staff', // Staff và Admin (vì Admin có TOÀN QUYỀN)
        ],
        [
            'text' => 'Quản lý Đánh giá',
            'url'  => 'admin/reviews',
            'icon' => 'fas fa-fw fa-star-half-alt',
            'can'  => 'staff', // Staff và Admin
        ],

        // MARKETING (Admin & Marketing)
        // Bắt đầu khối Marketing
        [
            'text' => 'Mã giảm giá (Voucher)',
            'url'  => 'admin/vouchers',
            'icon' => 'fas fa-fw fa-gift',
            'can'  => 'marketing', // Marketing và Admin
        ],

        // MỤC MỚI: CHƯƠNG TRÌNH GIẢM GIÁ SẢN PHẨM
        [
            'text' => 'Chương trình Giảm giá SP',
            'url'  => 'admin/product-discounts',
            'icon' => 'fas fa-fw fa-percent',
            'can'  => 'marketing', // Marketing và Admin
        ],

        [
            'text' => 'Slider/Banner', // Đã thêm module Slider
            'url'  => 'admin/sliders',
            'icon' => 'fas fa-fw fa-images',
            'can'  => 'marketing', // Marketing và Admin
        ],
        // Kết thúc khối Marketing

        // =========================================================================
        ['header' => 'CẤU HÌNH HỆ THỐNG', 'can' => 'admin'], // Chỉ Admin thấy header này

        // TÀI KHOẢN (Chỉ Admin)
        [
            'text' => 'Tài khoản nhân viên',
            'url'  => 'admin/employees',
            'icon' => 'fas fa-fw fa-users-cog',
            'can'  => 'admin', // Chỉ Admin
        ],

        // CẤU HÌNH CHUNG (Chỉ Admin)
        [
            'text'      => 'Cấu hình chung',
            'icon'      => 'fas fa-fw fa-cogs',
            'can'       => 'admin', // Chỉ Admin
            'submenu' => [
                [
                    'text' => 'Thương hiệu',
                    'url'  => 'admin/brands',
                    'icon' => 'far fa-fw fa-copyright',
                ],
                [
                    'text' => 'Danh mục sản phẩm',
                    'url'  => 'admin/categories',
                    'icon' => 'fas fa-fw fa-layer-group',
                ],
                // ⭐ MỤC THUỘC TÍNH SẢN PHẨM MỚI ⭐
                [
                    'text' => 'Thuộc tính Sản phẩm',
                    'url'  => 'admin/attributes', // Dùng route admin/attributes đã định nghĩa
                    'icon' => 'fas fa-fw fa-list-ul', // Icon gợi ý: list-ul hoặc tag
                    'active' => ['admin/attributes*'],
                ],
                // ⭐ HẾT MỤC THUỘC TÍNH SẢN PHẨM MỚI ⭐

                // QUẢN LÝ CẤU HÌNH VẬN CHUYỂN 
                [
                    'text' => 'Cấu hình Vận chuyển',
                    'icon' => 'fas fa-fw fa-sliders-h',
                    'submenu' => [
                        [
                            'text' => 'Đơn vị Vận chuyển',
                            'url'  => 'admin/carriers',
                            'icon' => 'fas fa-fw fa-truck',
                            'active' => ['admin/carriers*'],
                        ],
                        [
                            'text' => 'Mức phí Dịch vụ',
                            'url'  => 'admin/rates',
                            'icon' => 'fas fa-fw fa-list-alt',
                            'active' => ['admin/rates*'],
                        ],
                        // Trang cấu hình để thay đổi ngưỡng Free Ship
                        [
                            'text' => 'Ngưỡng Free Ship',
                            'url'  => 'admin/shipping/config', // Cần tạo route và controller cho mục này
                            'icon' => 'fas fa-fw fa-hand-holding-usd',
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    */

    'plugins' => [
        // ⭐ ĐÃ CHUYỂN VỀ DATATABLES CHUẨN ⭐
        'Datatables' => [
            'active' => true,  // <-- ĐỔI THÀNH TRUE
            'files' => [
                [
                    'type' => 'js',
                    'asset' => true, // Đảm bảo asset là true để nó tự động copy
                    'location' => 'vendor/datatables-bs4/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/datatables/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/datatables/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],

        // BỔ SUNG TOASTR CHO THÔNG BÁO AJAX
        'Toastr' => [
            'active' => true,  // <-- ĐỔI THÀNH TRUE
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true, // Đảm bảo asset là true
                    'location' => 'vendor/toastr/toastr.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/toastr/toastr.min.js',
                ],
            ],
        ],
        // Cấu hình plugin nếu cần
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    */

    'livewire' => [
        'enabled' => true,
        'scripts_path' => 'vendor/livewire/livewire.js',
    ],
];
