<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Đảm bảo đã import Log

class CategoryController extends Controller
{
    /**
     * Hiển thị danh sách các danh mục (READ).
     * Cập nhật để hỗ trợ lọc và thống kê
     */
    public function index(Request $request)
    {
        try {
            $status = $request->query('status', 'active');
            $query = Category::query();

            // Lọc theo trạng thái
            if ($status == 'active') {
                $query->where('is_active', 1);
            } elseif ($status == 'inactive') {
                $query->where('is_active', 0); // Lấy các tài khoản đã khóa
            }
            
            // Eager load 'products' để đếm số lượng SP đang active
            // SỬA LỖI: Thay .get() bằng .paginate() để khớp với file View
            $categories = $query->with('products')->orderBy('categoryName', 'asc')->paginate(10); 

            // Lấy số liệu thống kê
            $totalCategoryCount = Category::count();
            $activeCategoryCount = Category::where('is_active', 1)->count();
            $inactiveCategoryCount = Category::where('is_active', 0)->count();

            return view('admin.categories.index', compact(
                'categories', 
                'status',
                'totalCategoryCount',
                'activeCategoryCount',
                'inactiveCategoryCount'
            ));
        } catch (\Exception $e) {
            // ⭐ SỬA LỖI: Dùng dấu . (chấm) thay vì dấu (
            Log::error('Error in CategoryController@index: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi tải danh sách danh mục.');
        }
    }

    /**
     * Hiển thị form tạo mới (CREATE).
     */
    public function create()
    {
        return view('admin.categories.create');
    }

    /**
     * Lưu trữ dữ liệu Category mới vào DB (STORE).
     */
    public function store(Request $request)
    {
        $request->validate([
            'categoryName' => 'required|string|max:255|unique:categories,categoryName',
        ], [
            'categoryName.required' => 'Tên danh mục là bắt buộc.',
            'categoryName.unique' => 'Tên danh mục này đã tồn tại.',
        ]);

        try {
            Category::create([
                'categoryName' => $request->categoryName,
                'is_active' => 1 // Mặc định là active khi tạo mới
            ]);

            return redirect()->route('admin.categories.index')
                             ->with('success', 'Danh mục đã được thêm thành công.');
        } catch (\Exception $e) {
            Log::error('Error in CategoryController@store: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Có lỗi khi tạo danh mục.');
        }
    }

    /**
     * Hiển thị form chỉnh sửa (EDIT).
     * Cập nhật để tải các thuộc tính đã gán
     */
    public function edit(Category $category)
    {
        try {
            // Tải các thuộc tính đã gán cho danh mục này
            $assignedAttributes = $category->attributes()->with('values')->get();

            return view('admin.categories.edit', compact('category', 'assignedAttributes'));

        } catch (\Exception $e) {
            Log::error('Error in CategoryController@edit: ' . $e->getMessage());
            return back()->with('error', 'Không thể tải chi tiết danh mục.');
        }
    }

    /**
     * Cập nhật Category trong DB (UPDATE).
     * Cập nhật để hỗ trợ MỞ KHÓA (re-activate)
     */
    public function update(Request $request, Category $category)
    {
        // --- TRƯỜNG HỢP 1: MỞ KHÓA (từ trang index) ---
        if ($request->has('action_reactivate')) {
            try {
                $category->update(['is_active' => 1]);
                return redirect()->route('admin.categories.index', ['status' => 'inactive'])
                                 ->with('success', 'Đã kích hoạt lại danh mục "' . $category->categoryName . '".');
            } catch (\Exception $e) {
                Log::error('Error reactivating category: ' . $e->getMessage());
                return back()->with('error', 'Lỗi khi kích hoạt lại danh mục.');
            }
        }

        // --- TRƯỜNG HỢP 2: CẬP NHẬT THÔNG TIN (từ trang edit) ---
        $request->validate([
            'categoryName' => 'required|string|max:255|unique:categories,categoryName,' . $category->categoryID . ',categoryID',
            'is_active' => 'required|boolean', // Thêm validation
        ], [
            'categoryName.required' => 'Tên danh mục là bắt buộc.',
            'categoryName.unique' => 'Tên danh mục này đã tồn tại.',
        ]);
        
        try {
            $category->update([
                'categoryName' => $request->categoryName,
                'is_active' => $request->is_active, // Cập nhật trạng thái
            ]);

            return redirect()->route('admin.categories.index')
                             ->with('success', 'Danh mục đã được cập nhật thành công.');
        } catch (\Exception $e) {
            Log::error('Error in CategoryController@update: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Lỗi khi cập nhật danh mục.');
        }
    }

    /**
     * Xóa Category khỏi DB (DELETE/DESTROY).
     * Cập nhật để "ẨN" (Soft Delete)
     */
    public function destroy(Category $category)
    {
        try {
            // Kiểm tra xem có sản phẩm nào (kể cả sản phẩm ẩn) thuộc danh mục này không
            // Sử dụng quan hệ 'allProducts' đã thêm trong Model
            if ($category->allProducts()->exists()) {
                return redirect()->route('admin.categories.index')
                                 ->with('error', 'Không thể ẩn: Danh mục này đang được sử dụng bởi các sản phẩm.');
            }

            // THAY BẰNG "XÓA MỀM"
            $category->update(['is_active' => 0]);

            return redirect()->route('admin.categories.index')
                             ->with('success', 'Đã ẨN danh mục "' . $category->categoryName . '" thành công.');

        } catch (\Exception $e) {
            Log::error('Error in CategoryController@destroy: ' . $e->getMessage());
            return back()->with('error', 'Có lỗi xảy ra khi ẩn danh mục: ' . $e->getMessage());
        }
    }

    /**
     * Hàm show là không cần thiết cho module Categories.
     */
    public function show(Category $category)
    {
        return redirect()->route('admin.categories.index');
    }
}
