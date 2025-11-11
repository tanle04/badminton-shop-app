<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductDiscount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;

class AdminDiscountController extends Controller
{
    /**
     * Hiển thị danh sách các chương trình giảm giá (Web View).
     */
    public function index()
    {
        return view('admin.product_discounts.index');
    }

    /**
     * Trả về danh sách các chương trình giảm giá dưới dạng JSON (API).
     */
    public function apiIndex()
    {
        $discounts = ProductDiscount::orderBy('startDate', 'desc')->get();
        
        // ⭐ Thêm tên đối tượng để hiển thị
        $discounts = $discounts->map(function($discount) {
            $discount->appliedToName = $this->getAppliedToName($discount);
            return $discount;
        });
        
        return response()->json(['data' => $discounts]);
    }

    /**
     * ⭐ HÀM MỚI: Lấy tên đối tượng áp dụng
     */
    private function getAppliedToName($discount)
    {
        switch($discount->appliedToType) {
            case 'category':
                $category = Category::find($discount->appliedToID);
                return $category ? $category->categoryName : 'N/A';
            case 'brand':
                $brand = Brand::find($discount->appliedToID);
                return $brand ? $brand->brandName : 'N/A';
            case 'product':
                $product = Product::find($discount->appliedToID);
                return $product ? $product->productName : 'N/A';
            case 'variant':
                $variant = ProductVariant::find($discount->appliedToID);
                if ($variant) {
                    $product = Product::find($variant->productID);
                    return $product ? "{$product->productName} - {$variant->sku}" : $variant->sku;
                }
                return 'N/A';
            default:
                return 'N/A';
        }
    }

    /**
     * Hiển thị form tạo chương trình giảm giá mới.
     */
    public function create()
    {
        $categories = Category::select('categoryID', 'categoryName')->orderBy('categoryName')->get();
        $brands = Brand::select('brandID', 'brandName')->orderBy('brandName')->get();
        $products = Product::select('productID', 'productName')->where('is_active', 1)->orderBy('productName')->get();

        return view('admin.product_discounts.create', compact('categories', 'brands', 'products'));
    }

    /**
     * ⭐ API: Lấy variants của sản phẩm
     */
    public function getProductVariants($id)
    {
        $variants = ProductVariant::where('productID', $id)
                                  ->select('variantID', 'sku', 'price', 'stock')
                                  ->orderBy('sku')
                                  ->get();
        return response()->json($variants);
    }

    /**
     * ⭐ API: Lấy giá thấp nhất của đối tượng
     */
    public function getMinPrice(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');
        
        $minPrice = $this->calculateMinPrice($type, $id);
        
        return response()->json(['minPrice' => $minPrice]);
    }

    /**
     * ⭐ HÀM MỚI: Tính giá thấp nhất
     */
    private function calculateMinPrice($type, $id)
    {
        switch($type) {
            case 'variant':
                $variant = ProductVariant::find($id);
                return $variant ? $variant->price : 0;
                
            case 'product':
                return ProductVariant::where('productID', $id)->min('price') ?? 0;
                
            case 'category':
                $productIds = Product::where('categoryID', $id)->pluck('productID');
                return ProductVariant::whereIn('productID', $productIds)->min('price') ?? 0;
                
            case 'brand':
                $productIds = Product::where('brandID', $id)->pluck('productID');
                return ProductVariant::whereIn('productID', $productIds)->min('price') ?? 0;
                
            default:
                return 0;
        }
    }

    /**
     * ⭐ HÀM MỚI: Validate giá trị giảm giá
     */
    private function validateDiscountValue($discountType, $discountValue, $appliedToType, $appliedToID)
    {
        if ($discountType !== 'fixed') {
            if ($discountValue > 100) {
                throw ValidationException::withMessages([
                    'discountValue' => 'Giảm giá theo % không được vượt quá 100%.',
                ]);
            }
            return;
        }

        // Nếu là 'fixed', kiểm tra giá
        $minPrice = $this->calculateMinPrice($appliedToType, $appliedToID);
        
        if ($minPrice > 0 && $discountValue > $minPrice) {
            throw ValidationException::withMessages([
                'discountValue' => "Giá trị giảm (" . number_format($discountValue) . "đ) không được lớn hơn giá bán thấp nhất (" . number_format($minPrice) . "đ).",
            ]);
        }
    }

    /**
     * Lưu chương trình giảm giá mới
     */
    public function store(Request $request)
    {
        // 1. Validate dữ liệu cơ bản
        $validatedData = $request->validate([
            'discountName'      => 'required|string|max:255',
            'discountType'      => ['required', Rule::in(['percentage', 'fixed'])],
            'discountValue'     => 'required|numeric|min:0',
            'maxDiscountAmount' => 'nullable|numeric|min:0',
            'startDate'         => 'required|date',
            'endDate'           => 'required|date|after_or_equal:startDate',
            'appliedToType'     => ['required', Rule::in(['category', 'brand', 'product', 'variant'])],
            'appliedToID'       => 'required|integer|min:1',
            'isActive'          => 'sometimes|boolean',
        ]);

        // 2. Validate giá trị giảm giá
        $this->validateDiscountValue(
            $validatedData['discountType'],
            $validatedData['discountValue'],
            $validatedData['appliedToType'],
            $validatedData['appliedToID']
        );

        // 3. Xử lý checkbox isActive
        $validatedData['isActive'] = $request->has('isActive');

        // 4. Tạo bản ghi
        $discount = ProductDiscount::create($validatedData);

        if ($request->expectsJson()) {
            return response()->json($discount, 201); 
        }

        return redirect()->route('admin.product-discounts.index')
                         ->with('success', 'Đã tạo chương trình giảm giá thành công!');
    }

    /**
     * Hiển thị chi tiết một chương trình giảm giá
     */
    public function show($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        return response()->json($discount);
    }
    
    /**
     * Hiển thị form chỉnh sửa
     */
    public function edit($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        
        $categories = Category::select('categoryID', 'categoryName')->orderBy('categoryName')->get();
        $brands = Brand::select('brandID', 'brandName')->orderBy('brandName')->get();
        $products = Product::select('productID', 'productName')->where('is_active', 1)->orderBy('productName')->get();

        // ⭐ Lấy thông tin để pre-select
        $selectedProductID = null;
        $variantsForProduct = null;
        
        if ($discount->appliedToType == 'variant') {
            $variant = ProductVariant::find($discount->appliedToID);
            if ($variant) {
                $selectedProductID = $variant->productID;
                $variantsForProduct = ProductVariant::where('productID', $selectedProductID)
                                                    ->select('variantID', 'sku', 'price')
                                                    ->orderBy('sku')
                                                    ->get();
            }
        }

        return view('admin.product_discounts.edit', compact(
            'discount', 
            'categories', 
            'brands', 
            'products', 
            'selectedProductID',
            'variantsForProduct'
        ));
    }

    /**
     * Cập nhật chương trình giảm giá
     */
    public function update(Request $request, $id)
    {
        $discount = ProductDiscount::findOrFail($id);

        $validatedData = $request->validate([
            'discountName'      => 'sometimes|required|string|max:255',
            'discountType'      => ['sometimes','required', Rule::in(['percentage', 'fixed'])],
            'discountValue'     => 'sometimes|required|numeric|min:0',
            'maxDiscountAmount' => 'nullable|numeric|min:0',
            'startDate'         => 'sometimes|required|date',
            'endDate'           => 'sometimes|required|date|after_or_equal:startDate',
            'appliedToType'     => ['sometimes','required', Rule::in(['category', 'brand', 'product', 'variant'])],
            'appliedToID'       => 'sometimes|required|integer|min:1',
            'isActive'          => 'sometimes|boolean',
        ]);
        
        // Validate giá trị giảm giá
        $this->validateDiscountValue(
            $validatedData['discountType'],
            $validatedData['discountValue'],
            $validatedData['appliedToType'],
            $validatedData['appliedToID']
        );

        $validatedData['isActive'] = $request->has('isActive');
        $discount->update($validatedData);

        if ($request->expectsJson()) {
            return response()->json($discount);
        }

        return redirect()->route('admin.product-discounts.index')
                         ->with('success', 'Đã cập nhật chương trình giảm giá thành công!');
    }

    /**
     * Xóa chương trình giảm giá
     */
    public function destroy($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        $discount->delete();

        if (request()->expectsJson()) {
             return response()->json(['message' => 'Chương trình đã được xóa thành công.']);
        }
        
        return redirect()->route('admin.product-discounts.index')
                         ->with('success', 'Đã xóa chương trình giảm giá.');
    }

    /**
     * Toggle active/inactive
     */
    public function toggleActive($id)
    {
        $discount = ProductDiscount::findOrFail($id);
        $discount->isActive = !$discount->isActive;
        $discount->save();

        return response()->json(['isActive' => $discount->isActive, 'message' => 'Trạng thái đã được cập nhật.']);
    }
}