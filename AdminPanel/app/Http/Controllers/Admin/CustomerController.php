<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use Illuminate\Http\Request; // ⭐ Đảm bảo đã import Request
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Hiển thị danh sách các tài khoản khách hàng.
     * ⭐ Cập nhật để hỗ trợ lọc và thống kê
     */
    public function index(Request $request) // ⭐ Thêm Request $request
    {
        try {
            // Lấy status từ URL, mặc định là 'active' (đang hoạt động)
            $status = $request->query('status', 'active'); 

            $query = Customer::query();

            // Lọc theo status
            if ($status == 'active') {
                $query->where('is_active', 1);
            } elseif ($status == 'inactive') {
                $query->where('is_active', 0); // Lấy các tài khoản đã khóa
            }
            // Nếu status là 'all', không cần thêm điều kiện where

            // Lấy danh sách khách hàng đã lọc và phân trang
            $customers = $query->orderBy('createdDate', 'desc')->paginate(10);

            // Lấy số liệu thống kê cho các thẻ (card)
            $totalCustomerCount = Customer::count();
            $activeCustomerCount = Customer::where('is_active', 1)->count();
            $inactiveCustomerCount = Customer::where('is_active', 0)->count();
            
            // Trả về view với các biến
            return view('admin.customers.index', compact(
                'customers', 
                'status', 
                'totalCustomerCount', 
                'activeCustomerCount', 
                'inactiveCustomerCount'
            ));

        } catch (\Exception $e) {
            Log::error('Error in CustomerController@index: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return back()->with('error', 'Có lỗi xảy ra khi tải danh sách khách hàng: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị form tạo tài khoản khách hàng mới.
     */
    public function create()
    {
        return view('admin.customers.create');
    }

    /**
     * Lưu tài khoản khách hàng mới vào CSDL.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:customers,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            Customer::create([
                'fullName' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password_hash' => Hash::make($request->password),
                'isEmailVerified' => $request->isEmailVerified ?? 0,
                'is_active' => 1, // ⭐ Tự động kích hoạt tài khoản khi tạo
            ]);

            return redirect()->route('admin.customers.index')
                             ->with('success', 'Tạo tài khoản khách hàng thành công.');
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@store: ' . $e->getMessage());
            
            return back()->withInput()
                         ->with('error', 'Có lỗi xảy ra khi tạo tài khoản: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị chi tiết tài khoản khách hàng.
     */
    public function show(Customer $customer)
    {
        try {
            // Lấy tất cả địa chỉ của khách hàng (chỉ địa chỉ active)
            $addresses = CustomerAddress::where('customerID', $customer->customerID)
                                        ->where('is_active', 1) // ⭐ Đã có (Tốt!)
                                        ->orderBy('isDefault', 'desc')
                                        ->get();

            // Lấy tất cả đơn hàng của khách hàng
            $orders = Order::where('customerID', $customer->customerID)
                            ->orderBy('orderDate', 'desc')
                            ->paginate(10);

            // Tính tổng chi tiêu (chỉ đơn hàng đã thanh toán và đã giao)
            $totalSpent = Order::where('customerID', $customer->customerID)
                                ->whereIn('status', ['Delivered'])
                                ->where('paymentStatus', 'Paid')
                                ->sum('total');

            return view('admin.customers.show', compact('customer', 'addresses', 'orders', 'totalSpent'));
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@show: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return back()->with('error', 'Có lỗi xảy ra khi tải thông tin khách hàng: ' . $e->getMessage());
        }
    }

    /**
     * Hiển thị form chỉnh sửa tài khoản khách hàng.
     */
    public function edit(Customer $customer)
    {
        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Cập nhật thông tin tài khoản khách hàng.
     * ⭐ Cập nhật để hỗ trợ MỞ KHÓA (re-activate)
     */
    public function update(Request $request, Customer $customer)
    {
        // --- TRƯỜNG HỢP 1: MỞ KHÓA TÀI KHOẢN (từ trang index) ---
        // ⭐ Kiểm tra xem có input 'action_reactivate' được gửi từ form ẩn hay không
        if ($request->has('action_reactivate')) {
            try {
                $customer->update(['is_active' => 1]); // Đặt lại is_active = 1
                
                // Quay lại trang danh sách "Đã khóa" để admin thấy sự thay đổi
                return redirect()->route('admin.customers.index', ['status' => 'inactive'])
                                 ->with('success', 'Đã mở khóa tài khoản ' . $customer->fullName . ' thành công.');
            } catch (\Exception $e) {
                Log::error('Error in CustomerController@update (reactivate): ' . $e->getMessage());
                return redirect()->route('admin.customers.index')
                                 ->with('error', 'Lỗi khi mở khóa tài khoản: ' . $e->getMessage());
            }
        }

        // --- TRƯỜNG HỢP 2: CẬP NHẬT THÔNG TIN (từ trang edit) ---
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->customerID, 'customerID'),
            ],
            'phone' => 'required|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
            'isEmailVerified' => 'required|boolean',
            // ⭐ Thêm validation cho is_active nếu bạn có trường này trên form EDIT
            // 'is_active' => 'required|boolean', 
        ]);
        
        try {
            $customer->fullName = $request->name;
            $customer->email = $request->email;
            $customer->phone = $request->phone;
            $customer->isEmailVerified = $request->isEmailVerified;
            // ⭐ Thêm dòng này nếu bạn có trường is_active trên form EDIT
            // $customer->is_active = $request->is_active;

            if ($request->filled('password')) {
                $customer->password_hash = Hash::make($request->password);
            }
            
            $customer->save();

            // Quay về trang index (hoặc trang edit)
            return redirect()->route('admin.customers.index')
                             ->with('success', 'Cập nhật tài khoản khách hàng thành công.');
        } catch (\Exception $e) {
            Log::error('Error in CustomerController@update (edit): ' . $e->getMessage());
            
            return back()->withInput()
                         ->with('error', 'Có lỗi xảy ra khi cập nhật tài khoản: ' . $e->getMessage());
        }
    }

    /**
     * Xóa tài khoản khách hàng khỏi CSDL.
     * ⭐ Cập nhật để "KHÓA" (Soft Delete) thay vì xóa vĩnh viễn
     */
    public function destroy(Customer $customer)
    {
        try {
            // $customer->delete(); // <-- XÓA CỨNG (Gây lỗi)
            
            // ⭐ THAY BẰNG "XÓA MỀM" (Cập nhật is_active = 0)
            $customer->update(['is_active' => 0]);

            return redirect()->route('admin.customers.index')
                             ->with('success', 'Đã KHÓA tài khoản ' . $customer->fullName . ' thành công.'); // ⭐ Cập nhật thông báo
        
        } catch (\Exception $e) {
            // Lỗi này giờ ít khả năng xảy ra, nhưng vẫn nên giữ lại
            Log::error('Error in CustomerController@destroy (soft delete): ' . $e->getMessage());
            
            return back()->with('error', 'Có lỗi xảy ra khi khóa tài khoản: ' . $e->getMessage()); // ⭐ Cập nhật thông báo
        }
    }
}
