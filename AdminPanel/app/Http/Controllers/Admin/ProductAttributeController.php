<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductAttributeController extends Controller
{
    // =================================================================
    // QUẢN LÝ TÊN THUỘC TÍNH (ATTRIBUTES)
    // =================================================================

    /**
     * Hiển thị danh sách tất cả các thuộc tính sản phẩm.
     * QUAN TRỌNG: Phải truyền $categories vào view để modal hiển thị checkbox!
     */
    public function index(Request $request)
    {
        // Eager load 'values' để hiển thị trong Datatables
        $attributes = ProductAttribute::with('values')->orderBy('attributeID', 'asc')->get();
        
        // ⚠️ QUAN TRỌNG: Lấy tất cả categories để hiển thị checkbox trong modal
        // FIX: Thêm điều kiện để lấy categories đang active (nếu có)
        $categories = Category::orderBy('categoryName', 'asc')->get();
        
        // DEBUG: Log để kiểm tra số lượng categories
        \Log::info('Categories count in index: ' . $categories->count());
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'data' => $attributes->map(function ($attr) {
                    return [
                        'attributeID' => $attr->attributeID,
                        'attributeName' => $attr->attributeName,
                        'valuesCount' => count($attr->values),
                    ];
                })
            ]);
        }

        // ⚠️ QUAN TRỌNG: Phải có 'categories' trong compact()!
        return view('admin.attributes.index', compact('attributes', 'categories'));
    }

    /**
     * Hiển thị form tạo thuộc tính mới.
     */
    public function create()
    {
        return view('admin.attributes.create');
    }

    /**
     * Lưu thuộc tính mới vào DB.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'attributeName' => 'required|string|max:100|unique:product_attributes,attributeName',
        ], [
            'attributeName.unique' => 'Tên thuộc tính đã tồn tại.',
            'attributeName.required' => 'Tên thuộc tính không được để trống.',
            'attributeName.max' => 'Tên thuộc tính không được vượt quá 100 ký tự.',
        ]);

        // Kiểm tra nếu tên là "Size" - khuyến nghị đổi tên rõ ràng hơn
        if (strtolower(trim($validated['attributeName'])) === 'size') {
            return redirect()->back()
                ->withInput()
                ->with('warning', 'Tên "Size" quá chung chung. Nên dùng "Size Giày" hoặc "Size Quần Áo" để rõ ràng hơn.');
        }

        ProductAttribute::create($validated);

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Đã thêm thuộc tính "' . $validated['attributeName'] . '" thành công.');
    }

    /**
     * Hiển thị form chỉnh sửa tên thuộc tính.
     */
    public function edit(ProductAttribute $attribute)
    {
        return view('admin.attributes.edit', compact('attribute'));
    }

    /**
     * Cập nhật tên thuộc tính.
     */
    public function update(Request $request, ProductAttribute $attribute)
    {
        $validated = $request->validate([
            'attributeName' => 'required|string|max:100|unique:product_attributes,attributeName,' . $attribute->attributeID . ',attributeID',
        ], [
            'attributeName.unique' => 'Tên thuộc tính này đã tồn tại.',
            'attributeName.required' => 'Tên thuộc tính không được để trống.',
        ]);

        $attribute->update($validated);

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Cập nhật thuộc tính thành công.');
    }

    /**
     * Xóa thuộc tính và các giá trị liên quan.
     */
    public function destroy(ProductAttribute $attribute)
    {
        try {
            // Kiểm tra xem có variant nào đang dùng các giá trị của thuộc tính này không
            $variantCount = DB::table('variant_attribute_values as vav')
                ->join('product_attribute_values as pav', 'vav.valueID', '=', 'pav.valueID')
                ->where('pav.attributeID', $attribute->attributeID)
                ->count();

            if ($variantCount > 0) {
                return redirect()->route('admin.attributes.index')
                    ->with('error', 'Không thể xóa thuộc tính này. Có ' . $variantCount . ' biến thể sản phẩm đang sử dụng.');
            }

            // Xóa tất cả giá trị liên quan
            $attribute->values()->delete();

            // Xóa các liên kết trong category_attributes
            DB::table('category_attributes')
                ->where('attributeID', $attribute->attributeID)
                ->delete();

            // Xóa thuộc tính
            $attribute->delete();

            return redirect()->route('admin.attributes.index')
                ->with('success', 'Xóa thuộc tính và các giá trị liên quan thành công.');
        } catch (\Exception $e) {
            \Log::error('Error deleting attribute: ' . $e->getMessage());
            return redirect()->route('admin.attributes.index')
                ->with('error', 'Không thể xóa thuộc tính này. Lỗi: ' . $e->getMessage());
        }
    }

    // =================================================================
    // QUẢN LÝ GÁN DANH MỤC CHO THUỘC TÍNH
    // =================================================================

    /**
     * Lấy danh sách categories đã được gán cho attribute
     * Route: GET /admin/attributes/{attributeID}/categories
     */
    public function getAssignedCategories($attributeID)
    {
        try {
            $categoryIDs = DB::table('category_attributes')
                ->where('attributeID', $attributeID)
                ->pluck('categoryID')
                ->toArray();

            \Log::info('Assigned categories for attribute ' . $attributeID . ': ' . json_encode($categoryIDs));

            return response()->json($categoryIDs);
        } catch (\Exception $e) {
            \Log::error('Error getting assigned categories: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * Gán/Bỏ gán danh mục cho thuộc tính
     * Route: POST /admin/attributes/{attributeID}/assign-categories
     */
    public function assignCategories(Request $request, $attributeID)
    {
        try {
            $attribute = ProductAttribute::findOrFail($attributeID);
            
            // Lấy danh sách categories được chọn
            $selectedCategories = $request->input('categories', []);
            
            \Log::info('Assigning categories to attribute ' . $attributeID . ': ' . json_encode($selectedCategories));
            
            // Xóa tất cả liên kết cũ
            DB::table('category_attributes')
                ->where('attributeID', $attributeID)
                ->delete();
            
            // Thêm liên kết mới
            if (!empty($selectedCategories)) {
                foreach ($selectedCategories as $categoryID) {
                    DB::table('category_attributes')->insert([
                        'categoryID' => $categoryID,
                        'attributeID' => $attributeID,
                        'valueID_start' => null,
                        'valueID_end' => null,
                    ]);
                }
                
                $message = 'Đã gán ' . count($selectedCategories) . ' danh mục cho thuộc tính "' . $attribute->attributeName . '"';
            } else {
                $message = 'Đã bỏ gán tất cả danh mục khỏi thuộc tính "' . $attribute->attributeName . '"';
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('admin.attributes.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            \Log::error('Error assigning categories: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->route('admin.attributes.index')
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    // =================================================================
    // QUẢN LÝ GIÁ TRỊ THUỘC TÍNH (VALUES)
    // =================================================================

    /**
     * Hiển thị danh sách các giá trị cho một thuộc tính cụ thể.
     */
    public function showValues($attributeID)
    {
        $attribute = ProductAttribute::findOrFail($attributeID);
        $values = $attribute->values()->orderBy('valueID', 'asc')->get();

        // Lấy thông tin về các variant đang dùng mỗi giá trị
        foreach ($values as $value) {
            $value->usageCount = DB::table('variant_attribute_values')
                ->where('valueID', $value->valueID)
                ->count();
        }

        return view('admin.attributes.values.index', compact('attribute', 'values'));
    }

    /**
     * Lưu giá trị thuộc tính mới vào DB.
     */
    public function storeValue(Request $request, $attributeID)
    {
        $attribute = ProductAttribute::findOrFail($attributeID);

        $validated = $request->validate([
            'valueName' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('product_attribute_values')
                    ->where('attributeID', $attributeID)
            ],
        ], [
            'valueName.unique' => 'Giá trị "' . $request->valueName . '" đã tồn tại cho thuộc tính ' . $attribute->attributeName . '.',
            'valueName.required' => 'Tên giá trị không được để trống.',
            'valueName.max' => 'Tên giá trị không được vượt quá 100 ký tự.',
        ]);

        ProductAttributeValue::create([
            'attributeID' => $attributeID,
            'valueName' => $validated['valueName'],
        ]);

        return redirect()->route('admin.attributes.values.index', $attributeID)
            ->with('success', 'Đã thêm giá trị "' . $validated['valueName'] . '" thành công.');
    }

    /**
     * Cập nhật giá trị thuộc tính.
     */
    public function updateValue(Request $request, $attributeID, $valueID)
    {
        $value = ProductAttributeValue::where('attributeID', $attributeID)->findOrFail($valueID);

        $validated = $request->validate([
            'valueName' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('product_attribute_values')
                    ->where('attributeID', $attributeID)
                    ->ignore($valueID, 'valueID')
            ],
        ], [
            'valueName.unique' => 'Giá trị "' . $request->valueName . '" đã tồn tại.',
            'valueName.required' => 'Tên giá trị không được để trống.',
        ]);

        $value->update($validated);

        return redirect()->route('admin.attributes.values.index', $attributeID)
            ->with('success', 'Cập nhật giá trị thành công.');
    }

    /**
     * Xóa giá trị thuộc tính.
     */
    public function destroyValue($attributeID, $valueID)
    {
        $value = ProductAttributeValue::where('attributeID', $attributeID)->findOrFail($valueID);

        try {
            // Kiểm tra xem có variant nào đang sử dụng giá trị này không
            $variantCount = DB::table('variant_attribute_values')
                ->where('valueID', $valueID)
                ->count();

            if ($variantCount > 0) {
                return redirect()->route('admin.attributes.values.index', $attributeID)
                    ->with('error', 'Không thể xóa giá trị này. Có ' . $variantCount . ' biến thể sản phẩm đang sử dụng.');
            }

            $value->delete();

            return redirect()->route('admin.attributes.values.index', $attributeID)
                ->with('success', 'Xóa giá trị thuộc tính thành công.');
        } catch (\Exception $e) {
            return redirect()->route('admin.attributes.values.index', $attributeID)
                ->with('error', 'Không thể xóa giá trị này. Lỗi: ' . $e->getMessage());
        }
    }

    // =================================================================
    // HELPER METHODS
    // =================================================================

    /**
     * Lấy giá trị thuộc tính phù hợp với danh mục sản phẩm
     */
    public function getValuesByCategory($categoryID, $attributeID)
    {
        $catAttr = DB::table('category_attributes')
            ->where('categoryID', $categoryID)
            ->where('attributeID', $attributeID)
            ->first();
        
        if (!$catAttr) {
            return collect();
        }
        
        $query = ProductAttributeValue::where('attributeID', $attributeID);
        
        if ($catAttr->valueID_start && $catAttr->valueID_end) {
            $query->whereBetween('valueID', [
                $catAttr->valueID_start, 
                $catAttr->valueID_end
            ]);
        }
        
        return $query->orderBy('valueID')->get();
    }

    /**
     * Lấy tất cả thuộc tính của một danh mục
     */
    public function getAttributesByCategory($categoryID)
    {
        return DB::table('category_attributes as ca')
            ->join('product_attributes as pa', 'ca.attributeID', '=', 'pa.attributeID')
            ->where('ca.categoryID', $categoryID)
            ->select('pa.*', 'ca.valueID_start', 'ca.valueID_end')
            ->get();
    }
}