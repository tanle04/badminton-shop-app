<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Support\Facades\DB; // Thêm DB Facade để kiểm tra ràng buộc

class AttributeController extends Controller
{
    // =======================================================
    // VIEW Methods (Trả về Blade HTML)
    // =======================================================

    /**
     * GET /admin/attributes (Đã sửa route để gọi phương thức này)
     * Trả về View Blade AdminLTE cho danh sách thuộc tính.
     */
    public function manageIndex()
    {
        // View này sẽ load JavaScript DataTables, sau đó DataTables sẽ gọi API /admin/attributes/api-list
        return view('admin.attributes.index');
    }

    /**
     * GET /admin/attributes/create
     * Trả về form thêm thuộc tính mới.
     */
    public function create()
    {
        return view('admin.attributes.create');
    }

    /**
     * GET /admin/attributes/{attributeID}/edit
     * Trả về form chỉnh sửa thuộc tính và quản lý giá trị.
     */
    public function edit($attributeID)
    {
        $attribute = ProductAttribute::with('values')->find($attributeID);

        if (!$attribute) {
            return redirect()->route('admin.attributes.index')->with('error', 'Thuộc tính không tồn tại.');
        }

        return view('admin.attributes.edit', compact('attribute'));
    }

    // =======================================================
    // A. Quản lý Thuộc tính (API CRUD)
    // =======================================================

    /**
     * GET /admin/attributes/api-list (Route cũ index, đã đổi tên)
     * Lấy danh sách tất cả các Thuộc tính dưới dạng JSON.
     */
    public function index()
    {
        // Lấy thuộc tính cùng với tất cả các giá trị của nó
        $attributes = ProductAttribute::with('values')->get();
        return response()->json(['attributes' => $attributes]);
    }

    /**
     * POST /admin/attributes
     * Thêm thuộc tính mới.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attributeName' => 'required|string|max:100|unique:product_attributes,attributeName',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            ProductAttribute::create([
                'attributeName' => $request->attributeName,
            ]);
            return redirect()->route('admin.attributes.index')->with('success', 'Thuộc tính đã được thêm thành công!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi server khi thêm thuộc tính: ' . $e->getMessage());
        }
    }

    /**
     * PUT /admin/attributes/{attributeID}
     * Cập nhật tên thuộc tính.
     */
    public function update(Request $request, $attributeID)
    {
        $validator = Validator::make($request->all(), [
            'attributeName' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_attributes', 'attributeName')->ignore($attributeID, 'attributeID'),
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $attribute = ProductAttribute::find($attributeID);

        if (!$attribute) {
            return redirect()->back()->with('error', 'Thuộc tính không tồn tại.');
        }

        $attribute->update($request->only('attributeName'));

        return redirect()->back()->with('success', 'Tên thuộc tính đã được cập nhật thành công!');
    }

    /**
     * DELETE /admin/attributes/{attributeID}
     * Xóa thuộc tính (Có kiểm tra ràng buộc).
     */
    public function destroy($attributeID)
    {
        $attribute = ProductAttribute::with('values')->find($attributeID);

        if (!$attribute) {
            return response()->json(['message' => 'Thuộc tính không tồn tại.'], 404);
        }

        // 1. Kiểm tra xem có bất kỳ giá trị nào của thuộc tính này đang được sử dụng trong các biến thể không.
        $hasUsedValues = false;
        foreach ($attribute->values as $value) {
            if ($value->variants()->exists()) {
                $hasUsedValues = true;
                break;
            }
        }
        
        if ($hasUsedValues) {
            return response()->json([
                'message' => 'Không thể xóa thuộc tính này.', 
                'error' => 'Một hoặc nhiều giá trị của thuộc tính đang được sử dụng trong các biến thể sản phẩm (variants).'
            ], 409); // 409 Conflict
        }
        
        try {
            // 2. Xóa tất cả giá trị thuộc tính liên quan trước
            $attribute->values()->delete();
            
            // 3. Xóa thuộc tính chính
            $attribute->delete();
            
            return response()->json(['message' => 'Thuộc tính và các giá trị liên quan đã được xóa thành công!']);
        } catch (\Exception $e) {
             return response()->json([
                'message' => 'Lỗi server khi xóa thuộc tính.', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // =======================================================
    // B. Quản lý Giá trị Thuộc tính (JSON API CRUD)
    // =======================================================

    /**
     * GET /admin/attributes/values
     * Lấy danh sách tất cả các Giá trị Thuộc tính (JSON API).
     */
    public function valueIndex(Request $request)
    {
        $query = ProductAttributeValue::query();
        
        if ($request->has('attributeID')) {
            $query->where('attributeID', $request->attributeID);
        }
        
        $values = $query->with('attribute')->get();

        return response()->json(['attribute_values' => $values]);
    }

    /**
     * POST /admin/attributes/values
     * Thêm giá trị mới cho một thuộc tính (JSON API).
     */
    public function valueStore(Request $request)
    {
        // Đây là API endpoint, nên trả về JSON
        $validator = Validator::make($request->all(), [
            'attributeID' => 'required|integer|exists:product_attributes,attributeID',
            'valueName' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation Failed', 'errors' => $validator->errors()], 422);
        }

        $exists = ProductAttributeValue::where('attributeID', $request->attributeID)
            ->where('valueName', $request->valueName)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Giá trị đã tồn tại cho thuộc tính này.'], 409);
        }
        
        try {
            $value = ProductAttributeValue::create([
                'attributeID' => $request->attributeID,
                'valueName' => $request->valueName,
            ]);
            return response()->json([
                'message' => 'Giá trị thuộc tính đã được thêm thành công!',
                'value' => $value
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi server khi thêm giá trị thuộc tính.'], 500);
        }
    }

    /**
     * PUT /admin/attributes/values/{valueID}
     * Cập nhật tên giá trị thuộc tính (JSON API).
     */
    public function valueUpdate(Request $request, $valueID)
    {
        // Đây là API endpoint, nên trả về JSON
        $validator = Validator::make($request->all(), [
            'valueName' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation Failed', 'errors' => $validator->errors()], 422);
        }

        $value = ProductAttributeValue::find($valueID);

        if (!$value) {
            return response()->json(['message' => 'Giá trị không tồn tại.'], 404);
        }
        
        // Kiểm tra trùng tên trong cùng một thuộc tính (ví dụ: không thể có hai giá trị 'M' trong 'Size')
        $duplicate = ProductAttributeValue::where('attributeID', $value->attributeID)
            ->where('valueName', $request->valueName)
            ->where('valueID', '!=', $valueID)
            ->exists();
            
        if ($duplicate) {
            return response()->json(['message' => 'Giá trị đã tồn tại cho thuộc tính này.'], 409);
        }

        $value->update($request->only('valueName'));

        return response()->json(['message' => 'Giá trị thuộc tính đã được cập nhật thành công!']);
    }

    /**
     * DELETE /admin/attributes/values/{valueID}
     * Xóa giá trị thuộc tính (Có kiểm tra ràng buộc).
     */
    public function valueDestroy($valueID)
    {
        $value = ProductAttributeValue::find($valueID);

        if (!$value) {
            return response()->json(['message' => 'Giá trị không tồn tại.'], 404);
        }
        
        // Kiểm tra ràng buộc khóa ngoại: Kiểm tra xem giá trị này có được sử dụng trong bất kỳ variant nào không.
        if ($value->variants()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa giá trị thuộc tính này.', 
                'error' => 'Giá trị này đang được sử dụng bởi ít nhất một biến thể sản phẩm (variant).'
            ], 409); // 409 Conflict
        }

        try {
            $value->delete();
            return response()->json(['message' => 'Giá trị thuộc tính đã được xóa thành công!']);
        } catch (\Exception $e) {
             return response()->json([
                'message' => 'Lỗi server khi xóa giá trị thuộc tính.', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}