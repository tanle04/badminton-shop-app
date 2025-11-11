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

    'logo' => '<b>Badminton</b>Shop',
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
    'usermenu_header' => true,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => true, // Hiển thị role
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

    'use_route_url' => true,
    'dashboard_url' => 'admin.dashboard',
    'logout_url' => 'admin.logout',
    'login_url' => 'admin.login',
    'register_url' => false,
    'password_reset_url' => false,
    'password_email_url' => false,
    'profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Mix
    |--------------------------------------------------------------------------
    */

    'enabled_laravel_mix' => false,
    'laravel_mix_css_path' => 'css/app.css',
    'laravel_mix_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items - Phân quyền theo Role
    |--------------------------------------------------------------------------
    */

    'menu' => [
        
        // =====================================================================
        // COMMON - TẤT CẢ ROLES
        // =====================================================================
        
        [
            'text' => 'Dashboard',
            'route'  => 'admin.dashboard',
            'icon' => 'fas fa-fw fa-home',
            'active' => ['admin/dashboard'],
        ],

        [
            'text' => 'Chat Nội Bộ',
            'route'  => 'admin.chat.index',
            'icon' => 'fas fa-fw fa-comments',
            'active' => ['admin/chat'],
        ],

        [
            'text' => 'Hỗ trợ Khách hàng',
            'route'  => 'admin.support-chat.index',
            'icon' => 'fas fa-fw fa-headset',
            'active' => ['admin/support-chat'],
        ],

        // =====================================================================
        // QUẢN LÝ NGHIỆP VỤ
        // =====================================================================
        
        ['header' => 'QUẢN LÝ NGHIỆP VỤ'],

        // Khách hàng - TẤT CẢ ROLES (View)
        [
            'text' => 'Khách hàng',
            'route'  => 'admin.customers.index',
            'icon' => 'fas fa-fw fa-users',
            'active' => ['admin/customers*'],
        ],

        // Đơn hàng - TẤT CẢ ROLES
        [
            'text' => 'Đơn hàng',
            'route'  => 'admin.orders.index',
            'icon' => 'fas fa-fw fa-shopping-cart',
            'active' => ['admin/orders*'],
        ],

        // =====================================================================
        // KHO HÀNG - ADMIN & STAFF
        // =====================================================================
        
        ['header' => 'KHO HÀNG', 'can' => 'staff'],

        [
            'text' => 'Sản phẩm & Tồn kho',
            'route'  => 'admin.products.index',
            'icon' => 'fas fa-fw fa-box',
            'can'  => 'staff',
            'active' => ['admin/products*'],
        ],

        [
            'text' => 'Quản lý Đánh giá',
            'route'  => 'admin.reviews.index',
            'icon' => 'fas fa-fw fa-star-half-alt',
            'can'  => 'staff',
            'active' => ['admin/reviews*'],
        ],

        [
            'text' => 'Thuộc tính Sản phẩm',
            'route'  => 'admin.attributes.index',
            'icon' => 'fas fa-fw fa-list-ul',
            'can'  => 'staff',
            'active' => ['admin/attributes*'],
        ],

        // =====================================================================
        // MARKETING - ADMIN & MARKETER
        // =====================================================================
        
        ['header' => 'MARKETING', 'can' => 'marketing'],

        [
            'text' => 'Mã giảm giá (Voucher)',
            'route'  => 'admin.vouchers.index',
            'icon' => 'fas fa-fw fa-gift',
            'can'  => 'marketing',
            'active' => ['admin/vouchers*'],
        ],

        [
            'text' => 'Chương trình Giảm giá',
            'route'  => 'admin.product-discounts.index',
            'icon' => 'fas fa-fw fa-percent',
            'can'  => 'marketing',
            'active' => ['admin/product-discounts*'],
        ],

        [
            'text' => 'Slider/Banner',
            'route'  => 'admin.sliders.index',
            'icon' => 'fas fa-fw fa-images',
            'can'  => 'marketing',
            'active' => ['admin/sliders*'],
        ],

        // =====================================================================
        // CẤU HÌNH HỆ THỐNG - CHỈ ADMIN
        // =====================================================================
        
        ['header' => 'CẤU HÌNH HỆ THỐNG', 'can' => 'admin'],

        [
            'text' => 'Tài khoản nhân viên',
            'route'  => 'admin.employees.index',
            'icon' => 'fas fa-fw fa-users-cog',
            'can'  => 'admin',
            'active' => ['admin/employees*'],
        ],

        [
            'text'    => 'Danh mục & Thương hiệu',
            'icon'    => 'fas fa-fw fa-tags',
            'can'     => 'admin',
            'submenu' => [
                [
                    'text' => 'Thương hiệu',
                    'route'  => 'admin.brands.index',
                    'icon' => 'far fa-fw fa-copyright',
                    'active' => ['admin/brands*'],
                ],
                [
                    'text' => 'Danh mục sản phẩm',
                    'route'  => 'admin.categories.index',
                    'icon' => 'fas fa-fw fa-layer-group',
                    'active' => ['admin/categories*'],
                ],
            ],
        ],

        [
            'text' => 'Cấu hình Vận chuyển',
            'icon' => 'fas fa-fw fa-truck',
            'can'  => 'admin',
            'submenu' => [
                [
                    'text' => 'Đơn vị Vận chuyển',
                    'route'  => 'admin.carriers.index',
                    'icon' => 'fas fa-fw fa-shipping-fast',
                    'active' => ['admin/carriers*'],
                ],
                [
                    'text' => 'Mức phí Dịch vụ',
                    'route'  => 'admin.rates.index',
                    'icon' => 'fas fa-fw fa-dollar-sign',
                    'active' => ['admin/rates*'],
                ],
                [
                    'text' => 'Ngưỡng Free Ship',
                    'route'  => 'admin.shipping.config.edit',
                    'icon' => 'fas fa-fw fa-gift',
                    'active' => ['admin/shipping/config*'],
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
    | Plugins
    |--------------------------------------------------------------------------
    */

    'plugins' => [
        'Datatables' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/datatables-bs4/css/dataTables.bootstrap4.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/datatables/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/datatables-bs4/js/dataTables.bootstrap4.min.js',
                ],
            ],
        ],

        'Toastr' => [
            'active' => true,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => true,
                    'location' => 'vendor/toastr/toastr.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => true,
                    'location' => 'vendor/toastr/toastr.min.js',
                ],
            ],
        ],
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