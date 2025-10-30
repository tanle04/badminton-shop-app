<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\ProductAttribute;
use App\Models\CategoryAttribute; // Model cho category_attributes table

class CategoryAttributeController extends Controller
{
    /**
     * GET /admin/categories/{category}/attributes
     * Hiển thị giao diện quản lý liên kết Thuộc tính cho một Danh mục.
     */
    public function index(Category $category)
    {
        // Tải tất cả các thuộc tính hiện có và các giá trị của chúng
        $allAttributes = ProductAttribute::with('values')->orderBy('attributeID')->get();

        // Lấy cấu hình hiện tại của Danh mục
        $currentMappings = $category->categoryAttributes()->get()->keyBy('attributeID');

        return view('admin.categories.manage_attributes', compact('category', 'allAttributes', 'currentMappings'));
    }

    /**
     * POST /admin/categories/{category}/attributes
     * Xử lý lưu và đồng bộ hóa các liên kết Thuộc tính và Phạm vi Giá trị.
     */
    public function store(Request $request, Category $category)
    {
        // Lấy các dữ liệu chỉ khi checkbox 'enabled' được check
        $submittedAttributes = collect($request->input('attributes', []))
            ->filter(fn($data) => isset($data['enabled']))
            ->map(function($data) {
                // Chỉ giữ lại các trường liên quan đến database
                return [
                    'attributeID' => (int)$data['attributeID'],
                    'valueID_start' => $data['valueID_start'] ? (int)$data['valueID_start'] : null,
                    'valueID_end' => $data['valueID_end'] ? (int)$data['valueID_end'] : null,
                ];
            })->values()->toArray();
            
        // Chuẩn bị validation động dựa trên dữ liệu được submit
        $rules = [
            'valueID_start' => 'nullable|integer|min:1',
            'valueID_end' => 'nullable|integer|min:1',
        ];
        
        // Thêm rule kiểm tra valueID_end > valueID_start
        foreach ($submittedAttributes as $index => $data) {
            $rules["attributes.{$data['attributeID']}.valueID_end"] = [
                'nullable', 
                'integer',
                function ($attribute, $value, $fail) use ($data) {
                    if ($value !== null && $data['valueID_start'] !== null && $value <= $data['valueID_start']) {
                        $fail('ID Kết thúc phải lớn hơn ID Bắt đầu.');
                    }
                }
            ];
        }

        $request->validate($rules);
        $submittedAttributeIds = collect($submittedAttributes)->pluck('attributeID')->toArray();

        DB::beginTransaction();
        try {
            // 1. Xóa các mapping CŨ không được gửi lên
            $category->categoryAttributes()->whereNotIn('attributeID', $submittedAttributeIds)->delete();

            foreach ($submittedAttributes as $data) {
                // 2. Cập nhật hoặc Tạo mới mapping
                CategoryAttribute::updateOrCreate(
                    [
                        'categoryID' => $category->categoryID, 
                        'attributeID' => $data['attributeID']
                    ],
                    [
                        'valueID_start' => $data['valueID_start'],
                        'valueID_end' => $data['valueID_end'],
                    ]
                );
            }

            DB::commit();
            return redirect()->back()->with('success', 'Cấu hình thuộc tính cho danh mục ' . $category->categoryName . ' đã được cập nhật thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Lỗi khi cập nhật cấu hình: ' . $e->getMessage())->withInput();
        }
    }
}