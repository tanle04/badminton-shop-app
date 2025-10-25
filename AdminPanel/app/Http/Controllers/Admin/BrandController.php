<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Hiển thị danh sách các thương hiệu (READ).
     */
    public function index()
    {
        // Đã cập nhật: Sử dụng Eager Loading với `with('products')`
        // Điều này đảm bảo rằng relationship `products` được tải sẵn
        // và luôn là một Collection, giải quyết lỗi "count() on null".
        $brands = Brand::with('products')->get();
        
        // Trả về view admin.brands.index
        return view('admin.brands.index', compact('brands'));
    }

    /**
     * Hiển thị form tạo mới (CREATE).
     */
    public function create()
    {
        return view('admin.brands.create');
    }

    /**
     * Lưu trữ dữ liệu Brand mới vào DB (STORE).
     */
    public function store(Request $request)
    {
        $request->validate([
            // Yêu cầu tên thương hiệu, không trùng lặp trong bảng brands
            'brandName' => 'required|string|max:255|unique:brands,brandName',
        ], [
            'brandName.required' => 'Tên thương hiệu là bắt buộc.',
            'brandName.unique' => 'Tên thương hiệu này đã tồn tại.',
        ]);

        Brand::create($request->all());

        return redirect()->route('admin.brands.index')
                         ->with('success', 'Thương hiệu đã được thêm thành công.');
    }

    /**
     * Hiển thị form chỉnh sửa (EDIT).
     */
    public function edit(Brand $brand)
    {
        // $brand được Laravel tự động tìm kiếm qua brandID (vì route resource)
        return view('admin.brands.edit', compact('brand'));
    }

    /**
     * Cập nhật Brand trong DB (UPDATE).
     */
    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            // Bỏ qua brand hiện tại khi kiểm tra unique
            'brandName' => 'required|string|max:255|unique:brands,brandName,' . $brand->brandID . ',brandID',
        ], [
            'brandName.required' => 'Tên thương hiệu là bắt buộc.',
            'brandName.unique' => 'Tên thương hiệu này đã tồn tại.',
        ]);
        
        $brand->update($request->all());

        return redirect()->route('admin.brands.index')
                         ->with('success', 'Thương hiệu đã được cập nhật thành công.');
    }

    /**
     * Xóa Brand khỏi DB (DELETE/DESTROY).
     */
    public function destroy(Brand $brand)
    {
        // Cần kiểm tra quan hệ để tránh lỗi khóa ngoại
        // Đảm bảo method products() có tồn tại trong Brand Model
        if ($brand->products()->exists()) {
            return redirect()->route('admin.brands.index')
                             ->with('error', 'Không thể xóa: Thương hiệu này đang được sử dụng bởi các sản phẩm.');
        }

        $brand->delete();

        return redirect()->route('admin.brands.index')
                         ->with('success', 'Thương hiệu đã được xóa thành công.');
    }

    /**
     * Hàm show là không cần thiết cho module Brands, nhưng cần có để Resource Route hoạt động
     */
    public function show(Brand $brand)
    {
        return redirect()->route('admin.brands.index');
    }
}
