<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Đảm bảo đã import
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule; // Thêm thư viện Rule để fix lỗi unique

class EmployeeController extends Controller
{
    /**
     * Hiển thị danh sách các tài khoản nhân viên.
     */
    public function index()
    {
        $employees = Employee::orderBy('role')->paginate(10);
        
        return view('admin.employees.index', compact('employees'));
    }

    /**
     * Hiển thị form tạo tài khoản nhân viên mới.
     */
    public function create()
    {
        return view('admin.employees.create');
    }

    /**
     * Lưu tài khoản nhân viên mới vào CSDL.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:employees',
            'password' => 'required|string|min:8|confirmed', 
            'role' => 'required|in:admin,staff,marketing',
        ]);

        Employee::create([
            'fullName' => $request->name, // Sửa: Dùng fullName để khớp CSDL
            'email' => $request->email,
            'password' => Hash::make($request->password), // SỬA: Quay lại hash thủ công
            'role' => $request->role,
            // 'img_url' => 'default.png', // Có thể thêm giá trị mặc định nếu cần
        ]);

        return redirect()->route('admin.employees.index')
                            ->with('success', 'Tạo tài khoản nhân viên thành công.');
    }

    /**
     * Hiển thị form chỉnh sửa tài khoản nhân viên.
     */
    public function edit(Employee $employee)
    {
        // Sửa: Dùng employeeID thay cho id và đảm bảo so sánh với khóa chính
        if ($employee->employeeID === auth('admin')->id() && $employee->role !== 'admin') {
             return redirect()->route('admin.employees.index')
                             ->with('error', 'Bạn không thể chỉnh sửa role của tài khoản Admin đang đăng nhập.');
        }

        return view('admin.employees.edit', compact('employee'));
    }

    /**
     * Cập nhật thông tin tài khoản nhân viên.
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            // Sửa lỗi: Sử dụng Rule::unique để loại trừ theo khóa chính employeeID
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee->employeeID, 'employeeID'),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,staff,marketing',
        ]);
        
        // Ngăn Admin tự đổi role của chính mình
        // Sửa: Dùng employeeID thay cho id
        if ($employee->employeeID === auth('admin')->id() && $request->role !== 'admin') {
             return back()->with('error', 'Bạn không được phép thay đổi vai trò của tài khoản Admin đang đăng nhập.');
        }

        $employee->fullName = $request->name; // Sửa: Dùng fullName để khớp CSDL
        $employee->email = $request->email;
        $employee->role = $request->role;

        if ($request->filled('password')) {
            // SỬA: Quay lại Hash::make() vì tính năng 'hashed' trong Model không hoạt động
            $employee->password = Hash::make($request->password);
        }
        
        $employee->save();

        return redirect()->route('admin.employees.index')
                            ->with('success', 'Cập nhật tài khoản nhân viên thành công.');
    }

    /**
     * Xóa tài khoản nhân viên khỏi CSDL.
     */
    public function destroy(Employee $employee)
    {
        // Ngăn Admin tự xóa tài khoản của mình
        // Sửa: Dùng employeeID thay cho id
        if ($employee->employeeID === auth('admin')->id()) {
             return redirect()->route('admin.employees.index')
                             ->with('error', 'Bạn không thể tự xóa tài khoản của mình.');
        }
        
        $employee->delete();

        return redirect()->route('admin.employees.index')
                            ->with('success', 'Xóa tài khoản nhân viên thành công.');
    }
}
