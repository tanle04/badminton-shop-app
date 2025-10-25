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
    'logout_url' => 'admin/logout',
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

    // config/adminlte.php (PHẦN ĐÃ CHỈNH SỬA)

    'menu' => [
        // Menu quan trọng: Dashboard (Tất cả nhân viên đã đăng nhập)
        [
            'text' => 'Dashboard',
            'url'  => 'admin/dashboard',
            'icon' => 'fas fa-fw fa-home',
            'active' => ['admin/dashboard'],
        ],

        // =========================================================================
        ['header' => 'QUẢN LÝ NGHIỆP VỤ', 'can' => 'auth:admin'], // Tất cả đều thấy header này

        // ĐƠN HÀNG (Tất cả nhân viên xem)
        [
            'text' => 'Đơn hàng & Vận chuyển',
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
        [
            'text' => 'Mã giảm giá (Voucher)',
            'url'  => 'admin/vouchers',
            'icon' => 'fas fa-fw fa-gift',
            'can'  => 'marketing', // Marketing và Admin
        ],
        [
            'text' => 'Slider/Banner', // Đã thêm module Slider
            'url'  => 'admin/sliders',
            'icon' => 'fas fa-fw fa-images',
            'can'  => 'marketing', // Marketing và Admin
        ],

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
            'text'    => 'Cấu hình chung',
            'icon'    => 'fas fa-fw fa-cogs',
            'can'     => 'admin', // Chỉ Admin
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
