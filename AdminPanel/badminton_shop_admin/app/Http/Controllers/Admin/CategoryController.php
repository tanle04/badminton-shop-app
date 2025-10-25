<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Hiển thị danh sách các danh mục (READ).
     */
    public function index()
    {
        // Lấy tất cả danh mục
        $categories = Category::all();
        
        // Trả về view admin.categories.index
        return view('admin.categories.index', compact('categories'));
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
            // Yêu cầu tên danh mục, không trùng lặp trong bảng categories
            'categoryName' => 'required|string|max:255|unique:categories,categoryName',
        ], [
            'categoryName.required' => 'Tên danh mục là bắt buộc.',
            'categoryName.unique' => 'Tên danh mục này đã tồn tại.',
        ]);

        Category::create($request->all());

        return redirect()->route('admin.categories.index')
                         ->with('success', 'Danh mục đã được thêm thành công.');
    }

    /**
     * Hiển thị form chỉnh sửa (EDIT).
     */
    public function edit(Category $category)
    {
        // $category được Laravel tự động tìm kiếm qua categoryID
        return view('admin.categories.edit', compact('category'));
    }

    /**
     * Cập nhật Category trong DB (UPDATE).
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            // Bỏ qua chính bản thân nó khi kiểm tra unique
            'categoryName' => 'required|string|max:255|unique:categories,categoryName,' . $category->categoryID . ',categoryID',
        ], [
            'categoryName.required' => 'Tên danh mục là bắt buộc.',
            'categoryName.unique' => 'Tên danh mục này đã tồn tại.',
        ]);
        
        $category->update($request->all());

        return redirect()->route('admin.categories.index')
                         ->with('success', 'Danh mục đã được cập nhật thành công.');
    }

    /**
     * Xóa Category khỏi DB (DELETE/DESTROY).
     */
    public function destroy(Category $category)
    {
        // Kiểm tra quan hệ khóa ngoại (kiểm tra xem có sản phẩm nào thuộc danh mục này không)
        if ($category->products()->exists()) {
            return redirect()->route('admin.categories.index')
                             ->with('error', 'Không thể xóa: Danh mục này đang được sử dụng bởi các sản phẩm.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
                         ->with('success', 'Danh mục đã được xóa thành công.');
    }

    /**
     * Hàm show là không cần thiết cho module Categories.
     */
    public function show(Category $category)
    {
        return redirect()->route('admin.categories.index');
    }
}
