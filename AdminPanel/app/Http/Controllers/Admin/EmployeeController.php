<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log; // ⭐ Thêm Log
 
class EmployeeController extends Controller
{
    /**
     * Hiển thị danh sách các tài khoản nhân viên.
     * ⭐ Cập nhật để hỗ trợ lọc và thống kê
     */
    public function index(Request $request)
    {
        try {
            $status = $request->query('status', 'active');

            $query = Employee::query();

            // Lọc theo trạng thái
            if ($status == 'active') {
                $query->where('is_active', 1);
            } elseif ($status == 'inactive') {
                $query->where('is_active', 0);
            }
            
            $employees = $query->orderBy('role')->paginate(10);

            // Lấy số liệu thống kê
            $totalEmployeeCount = Employee::count();
            $activeEmployeeCount = Employee::where('is_active', 1)->count();
            $inactiveEmployeeCount = Employee::where('is_active', 0)->count();

            return view('admin.employees.index', compact(
                'employees', 
                'status',
                'totalEmployeeCount',
                'activeEmployeeCount',
                'inactiveEmployeeCount'
            ));
        } catch (\Exception $e) {
            Log::error('Error in EmployeeController@index: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi tải danh sách nhân viên.');
        }
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
            'email' => 'required|string|email|max:255|unique:employees,email',
            'password' => 'required|string|min:8|confirmed', 
            'role' => 'required|in:admin,staff,marketing',
        ]);

        try {
            Employee::create([
                'fullName' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password), 
                'role' => $request->role,
                'is_active' => 1, // ⭐ Mặc định là active khi tạo mới
            ]);

            return redirect()->route('admin.employees.index')
                             ->with('success', 'Tạo tài khoản nhân viên thành công.');
        } catch (\Exception $e) {
            Log::error('Error in EmployeeController@store: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi xảy ra khi tạo tài khoản.');
        }
    }

    /**
     * Hiển thị form chỉnh sửa tài khoản nhân viên.
     */
    public function edit(Employee $employee)
    {
        // Logic kiểm tra (đã có)
        if ($employee->employeeID === auth('admin')->id() && $employee->role !== 'admin') {
             return redirect()->route('admin.employees.index')
                             ->with('error', 'Bạn không thể chỉnh sửa role của tài khoản Admin đang đăng nhập.');
        }

        return view('admin.employees.edit', compact('employee'));
    }

    /**
     * Cập nhật thông tin tài khoản nhân viên.
     * ⭐ Cập nhật để hỗ trợ MỞ KHÓA (re-activate)
     */
    public function update(Request $request, Employee $employee)
    {
        // --- TRƯỜNG HỢP 1: MỞ KHÓA TÀI KHOẢN (từ trang index) ---
        if ($request->has('action_reactivate')) {
            try {
                $employee->update(['is_active' => 1]); // Đặt lại is_active = 1
                return redirect()->route('admin.employees.index', ['status' => 'inactive'])
                                 ->with('success', 'Đã MỞ KHÓA tài khoản ' . $employee->fullName . ' thành công.');
            } catch (\Exception $e) {
                Log::error('Error in EmployeeController@update (reactivate): ' . $e->getMessage());
                return redirect()->route('admin.employees.index')
                                 ->with('error', 'Lỗi khi mở khóa tài khoản: ' . $e->getMessage());
            }
        }

        // --- TRƯỜNG HỢP 2: CẬP NHẬT THÔNG TIN (từ trang edit) ---
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('employees', 'email')->ignore($employee->employeeID, 'employeeID'),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,staff,marketing',
            'is_active' => 'required|boolean', // ⭐ Thêm validation cho trường is_active
        ]);
        
        // Ngăn Admin tự đổi role của chính mình
        if ($employee->employeeID === auth('admin')->id() && $request->role !== 'admin') {
             return back()->with('error', 'Bạn không được phép thay đổi vai trò của tài khoản Admin đang đăng nhập.');
        }
        
        // ⭐ Ngăn Admin tự KHÓA chính mình
        if ($employee->employeeID === auth('admin')->id() && $request->is_active == 0) {
            return back()->with('error', 'Bạn không thể tự khóa tài khoản của chính mình.');
       }

        try {
            $employee->fullName = $request->name;
            $employee->email = $request->email;
            $employee->role = $request->role;
            $employee->is_active = $request->is_active; // ⭐ Cập nhật trường is_active

            if ($request->filled('password')) {
                $employee->password = Hash::make($request->password);
            }
            
            $employee->save();

            return redirect()->route('admin.employees.index')
                             ->with('success', 'Cập nhật tài khoản nhân viên thành công.');
        } catch (\Exception $e) {
            Log::error('Error in EmployeeController@update (edit): ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi khi cập nhật tài khoản.');
        }
    }

    /**
     * Xóa tài khoản nhân viên khỏi CSDL.
     * ⭐ Cập nhật để "KHÓA" (Soft Delete)
     */
    public function destroy(Employee $employee)
    {
        // Ngăn Admin tự xóa/khóa tài khoản của mình
        if ($employee->employeeID === auth('admin')->id()) {
             return redirect()->route('admin.employees.index')
                             ->with('error', 'Bạn không thể tự khóa tài khoản của mình.');
        }
        
        try {
            // $employee->delete(); // <-- XÓA CỨNG (Không dùng nữa)
            
            // ⭐ THAY BẰNG "XÓA MỀM"
            $employee->update(['is_active' => 0]); 

            return redirect()->route('admin.employees.index')
                             ->with('success', 'Đã KHÓA tài khoản ' . $employee->fullName . ' thành công.');
        } catch (\Exception $e) {
            Log::error('Error in EmployeeController@destroy (soft delete): ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi khóa tài khoản: ' . $e->getMessage());
        }
    }
}
