<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. 
|
*/

// Route mặc định cho trang chủ hoặc trang chào mừng
Route::get('/', function () {
    return view('welcome');
});

// THÊM CÁC ROUTE PHÍA NGƯỜI DÙNG (CUSTOMER/FRONTEND) VÀO ĐÂY
// (Tất cả các route Admin đã được chuyển sang routes/admin.php)