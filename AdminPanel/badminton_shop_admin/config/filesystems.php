<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        // 1. DISK MẶC ĐỊNH 'PUBLIC' CỦA LARAVEL
        // Cần giữ nguyên cấu hình này cho các mục đích chung của Laravel
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        // 2. DISK CHO THƯ MỤC UPLOADS CŨ CỦA API PHP GỐC
        // Đường dẫn: C:\xampp\htdocs\api\BadmintonShop\images\products
        'api_legacy_uploads' => [
            'driver' => 'local',
            // Chỉ định thư mục 'images' là root. Sau đó, trong Controller, ta dùng 'products'
            'root' => 'C:/xampp/htdocs/api/BadmintonShop/images',
            'url' => 'http://127.0.0.1/api/BadmintonShop/images', // Sửa lại URL cho chính xác
            'visibility' => 'public',
            'throw' => false,
        ],

        // 3. DISK CHO THƯ MỤC UPLOADS DÀNH RIÊNG CHO SẢN PHẨM TRONG ADMIN (SỬ DỤNG PUBLIC)
        // Đường dẫn: C:\xampp\htdocs\badminton_shop_admin\storage\app\public\products
        // Chúng ta có thể dùng 'public' mặc định. Tuy nhiên, nếu muốn đặt tên rõ ràng:
        'admin_public_products' => [
            'driver' => 'local',
            'root' => storage_path('app/public'), // Vẫn trỏ về root public, folder 'products' sẽ được Controller xử lý
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
        // Trong config/filesystems.php, thêm vào mảng 'disks'
        'api_client_uploads' => [
            'driver' => 'local',
            // Sử dụng base_path() để đi từ badminton_shop_admin đến api/BadmintonShop/uploads
            // (Giả định thư mục uploads nằm trong BadmintonShop hoặc ngang hàng với api)
            'root' => base_path() . '/../api/BadmintonShop/uploads',
            'url' => env('APP_URL') . '/api/BadmintonShop/uploads',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
