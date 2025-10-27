<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;

class AdminDiscountController extends Controller
{
    /**
     * Hiển thị danh sách các chương trình giảm giá (Web View).
     */
    public function index()
    {
        // Trả về view HTML cho Admin Panel
        return view('admin.product_discounts.index');
    }

    /**
     * Trả về danh sách các chương trình giảm giá dưới dạng JSON (API).
     */
    public function apiIndex()
    {
        $discounts = ProductDiscount::orderBy('startDate', 'desc')->paginate(15);
        return response()->json($discounts);
    }

    /**
     * Hiển thị form tạo chương trình giảm giá mới.
     */
    public function create()
    {
        // Lấy dữ liệu cần thiết cho form (đã thêm Model tương ứng ở phần use)
        $categories = Category::select('categoryID', 'categoryName')->get();
        $brands = Brand::select('brandID', 'brandName')->get();
        $products = Product::select('productID', 'productName')->get();

        return view('admin.product_discounts.create', compact('categories', 'brands', 'products'));
    }

    /**
     * Lưu chương trình giảm giá mới (API POST / Web Form Submit).
     */
    public function store(Request $request)
    {
        // 1. Validate dữ liệu đầu vào
        $validatedData = $request->validate([
            'discountName'      => 'required|string|max:255',
            'discountType'      => ['required', Rule::in(['percentage', 'fixed'])],
            'discountValue'     => 'required|numeric|min:0',
            'maxDiscountAmount' => 'nullable|numeric|min:0',
            'startDate'         => 'required|date',
            'endDate'           => 'required|date|after_or_equal:startDate',
            'appliedToType'     => ['required', Rule::in(['category', 'brand', 'product', 'variant'])],
            'appliedToID'       => 'required|integer|min:1', // ID của đối tượng áp dụng
            'isActive'          => 'boolean',
        ]);

        // Xử lý giá trị boolean cho isActive (vì form checkbox chỉ gửi khi được chọn)
        $validatedData['isActive'] = $request->has('isActive');

        // 2. Tạo bản ghi
        $discount = ProductDiscount::create($validatedData);

        // 3. XỬ LÝ PHẢN HỒI: Chuyển hướng cho Web, JSON cho API
        if ($request->expectsJson()) {
            return response()->json($discount, 201); 
        }

        // Chuyển hướng về trang danh sách (Web)
        return redirect()->route('admin.product-discounts.index')
                         ->with('success', 'Đã tạo chương trình giảm giá thành công!');
    }

    /**
     * Hiển thị chi tiết một chương trình giảm giá (API GET).
     */
    public function show($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        return response()->json($discount);
    }
    
    /**
     * Hiển thị form chỉnh sửa chương trình giảm giá (Web View).
     */
    public function edit($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        
        // Lấy dữ liệu cần thiết cho form
        $categories = Category::select('categoryID', 'categoryName')->get();
        $brands = Brand::select('brandID', 'brandName')->get();
        $products = Product::select('productID', 'productName')->get();

        return view('admin.product_discounts.edit', compact('discount', 'categories', 'brands', 'products'));
    }

    /**
     * Cập nhật chương trình giảm giá (API PUT/PATCH).
     */
    public function update(Request $request, $id)
    {
        $discount = ProductDiscount::findOrFail($id);

        $validatedData = $request->validate([
            'discountName'      => 'sometimes|string|max:255',
            'discountType'      => ['sometimes', Rule::in(['percentage', 'fixed'])],
            'discountValue'     => 'sometimes|numeric|min:0',
            'maxDiscountAmount' => 'nullable|numeric|min:0',
            'startDate'         => 'sometimes|date',
            'endDate'           => 'sometimes|date|after_or_equal:startDate',
            'appliedToType'     => ['sometimes', Rule::in(['category', 'brand', 'product', 'variant'])],
            'appliedToID'       => 'sometimes|integer|min:1',
            'isActive'          => 'sometimes|boolean',
        ]);
        
        // Xử lý giá trị boolean cho isActive
        $validatedData['isActive'] = $request->has('isActive');

        $discount->update($validatedData);

        // Xử lý phản hồi chuyển hướng cho Web
        if ($request->expectsJson()) {
            return response()->json($discount);
        }

        return redirect()->route('admin.product-discounts.index')
                         ->with('success', 'Đã cập nhật chương trình giảm giá thành công!');
    }

    /**
     * Xóa chương trình giảm giá (API DELETE).
     */
    public function destroy($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        $discount->delete();

        // Luôn trả về 204 cho API Delete
        return response()->json(null, 204); 
    }

    /**
     * API để Tắt/Bật chương trình sale nhanh chóng (API PUT).
     */
    public function toggleActive($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        $discount->isActive = !$discount->isActive;
        $discount->save();

        return response()->json(['isActive' => $discount->isActive, 'message' => 'Trạng thái đã được cập nhật.']);
    }
}
