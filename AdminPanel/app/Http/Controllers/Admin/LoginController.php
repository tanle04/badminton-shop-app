<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    // Constructor để áp dụng Middleware 'guest' cho các phương thức này
    public function __construct()
    {
        // Chỉ khách (chưa đăng nhập) mới được xem login form.
        $this->middleware('guest:admin')->except('logout');
    }

    /**
     * Hiển thị form đăng nhập Admin (dùng view tùy chỉnh).
     */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /**
     * Xử lý logic đăng nhập.
     */
    public function login(Request $request)
    {
        // 1. Xác thực đầu vào
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        
        // 2. Auth::attempt với Guard 'admin'
        if (Auth::guard('admin')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Chuyển hướng đến Dashboard Admin (admin.dashboard)
            return redirect()->intended(route('admin.dashboard')); 
        }

        // 3. Đăng nhập thất bại
        throw ValidationException::withMessages([
            'email' => ['Thông tin đăng nhập không hợp lệ hoặc tài khoản không tồn tại.'],
        ]);
    }

    /**
     * Xử lý logic đăng xuất.
     */
    public function logout(Request $request)
    {
        // Logout guard 'admin'
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Chuyển hướng đến trang đăng nhập Admin (đã fix lỗi 404 /home)
        return redirect()->route('admin.login');
    }
}