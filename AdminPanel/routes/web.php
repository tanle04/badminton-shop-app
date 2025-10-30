<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // Kiểm tra xem admin đã đăng nhập chưa
    if (Auth::guard('admin')->check()) {
        // Nếu đã đăng nhập, chuyển hướng đến dashboard
        return redirect()->route('admin.dashboard');
    }
    // Nếu chưa, hiển thị trang login
    return redirect()->route('admin.login');
});

// KHÔNG CÓ GÌ KHÁC Ở ĐÂY.
// File admin.php đã được gọi trong RouteServiceProvider.