<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductAttribute;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    const FOLDER_PATH = 'products';

    /**
     * Hiển thị danh sách sản phẩm
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'active');
        $query = Product::with(['category', 'brand', 'variants'])
            ->latest('productID');

        if ($status === 'active') {
            $query->where('is_active', 1);
        } elseif ($status === 'inactive') {
            $query->where('is_active', 0);
        }

        $products = $query->paginate(10);
        return view('admin.products.index', compact('products', 'status'));
    }

    /**
     * Form tạo sản phẩm mới - CẢI TIẾN
     */
    public function create()
    {
        $categories = Category::all();
        $brands = Brand::all();
        
        // Lấy tất cả thuộc tính để client-side filtering
        $attributes = ProductAttribute::with('values')->get();
        
        // Lấy mapping category -> attributes từ DB
        $categoryAttributes = $this->getCategoryAttributesMapping();

        return view('admin.products.create', compact(
            'categories', 
            'brands', 
            'attributes',
            'categoryAttributes'
        ));
    }

    /**
     * Lưu sản phẩm mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'productName' => 'required|string|max:255',
            'categoryID' => 'required|exists:categories,categoryID',
            'brandID' => 'required|exists:brands,brandID',
            'images.main' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'variants' => 'required|array',
            'variants.*.sku' => 'required|distinct|max:50|unique:product_variants,sku',
            'variants.*.price' => 'required|numeric|min:1000',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.attribute_values' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            $totalStock = 0;
            $minPrice = PHP_INT_MAX;

            $product = Product::create([
                'productName' => $request->productName,
                'description' => $request->description,
                'categoryID' => $request->categoryID,
                'brandID' => $request->brandID,
                'price' => $request->variants[0]['price'],
                'stock' => 0,
                // Mặc định sản phẩm mới là active
                'is_active' => 1, 
            ]);

            foreach ($request->variants as $variantData) {
                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'reservedStock' => 0,
                    'is_active' => 1, // <-- THÊM MỚI: Đặt là active
                ]);

                $totalStock += $variant->stock;
                $minPrice = min($minPrice, $variant->price);

                if (isset($variantData['attribute_values'])) {
                    $variant->attributeValues()->attach($variantData['attribute_values']);
                }
            }

            $product->stock = $totalStock;
            $product->price = $minPrice;
            $product->save();

            $this->handleImageUpload($request, $product);

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('success', 'Sản phẩm mới đã được tạo thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi khi tạo sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Form chỉnh sửa sản phẩm - CẢI TIẾN
     */
    public function edit(Product $product)
    {
        $product->load([
            'category',
            'brand',
            // Load TẤT CẢ variants, bao gồm cả active và inactive
            'variants.attributeValues.attribute', 
            'images'
        ]);

        $categories = Category::all();
        $brands = Brand::all();
        
        // Lấy tất cả thuộc tính
        $attributes = ProductAttribute::with('values')->get();
        
        // Lấy mapping category -> attributes
        $categoryAttributes = $this->getCategoryAttributesMapping();
        
        // Lấy các thuộc tính đã được gán cho category hiện tại
        $currentCategoryAttributes = $this->getAttributesByCategory($product->categoryID);

        return view('admin.products.edit', compact(
            'product',
            'categories',
            'brands',
            'attributes',
            'categoryAttributes',
            'currentCategoryAttributes'
        ));
    }

    /**
     * Cập nhật sản phẩm
     */
    public function update(Request $request, Product $product)
    {
        // Logic kích hoạt lại
        if ($request->has('action_reactivate')) {
            $product->is_active = 1;
            $product->save();
            return redirect()->route('admin.products.index', ['status' => 'active'])
                ->with('success', 'Sản phẩm "' . $product->productName . '" đã được KÍCH HOẠT LẠI thành công!');
        }

        $request->validate([
            'productName' => 'required|string|max:255',
            'categoryID' => 'required|exists:categories,categoryID',
            'brandID' => 'required|exists:brands,brandID',
            'images.main' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'variants' => 'required|array',
            'variants.*.id' => [
                'required',
                // Cho phép 'NEW' hoặc ID của bất kỳ variant nào (kể cả inactive)
                'in:NEW,' . $product->variants()->pluck('variantID')->implode(','),
            ],
            'variants.*.price' => 'required|numeric|min:1000',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.attribute_values' => 'required|array',
            'variants.*.sku' => [
                'required',
                'max:50',
                function ($attribute, $value, $fail) use ($request, $product) {
                    $index = explode('.', $attribute)[1];
                    $variantId = $request->variants[$index]['id'] === 'NEW' ? null : $request->variants[$index]['id'];

                    if (empty($value)) return;

                    $exists = DB::table('product_variants')
                        ->where('sku', $value)
                        ->where('variantID', '!=', $variantId)
                        ->exists();

                    if ($exists) {
                        $fail('SKU "' . $value . '" đã tồn tại cho một sản phẩm khác.');
                    }
                },
            ],
        ]);

        try {
            DB::beginTransaction();

            $product->update([
                'productName' => $request->productName,
                'description' => $request->description,
                'categoryID' => $request->categoryID,
                'brandID' => $request->brandID,
            ]);

            // Lấy TẤT CẢ variant IDs hiện tại của sản phẩm
            $allCurrentVariantIds = $product->variants->pluck('variantID')->toArray();

            // Lấy các variant IDs được submit từ form (chỉ những cái đã tồn tại, không phải "NEW")
            $submittedExistingVariantIds = collect($request->variants)
                ->where('id', '!=', 'NEW')
                ->pluck('id')
                ->map(fn($id) => (int)$id) // Đảm bảo là integer
                ->toArray();

            // 1. Vô hiệu hóa (disable) các variants không còn trong form
            // Đây là những variant BỊ BỎ TICK
            $variantIdsToDisable = array_diff($allCurrentVariantIds, $submittedExistingVariantIds);
            
            if (!empty($variantIdsToDisable)) {
                ProductVariant::whereIn('variantID', $variantIdsToDisable)
                              ->update(['is_active' => 0]);
            }

            // 2. Cập nhật hoặc Tạo mới / Kích hoạt lại variants
            foreach ($request->variants as $variantData) {
                $variantPayload = [
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'is_active' => 1, // <-- QUAN TRỌNG: Luôn set là active khi submit
                ];

                $attributeValues = $variantData['attribute_values'] ?? [];

                if ($variantData['id'] === 'NEW') {
                    // Tạo mới
                    $variantPayload['reservedStock'] = 0;
                    $variant = $product->variants()->create($variantPayload);
                    $variant->attributeValues()->sync($attributeValues);

                } else {
                    // Cập nhật (hoặc kích hoạt lại)
                    $variant = ProductVariant::find($variantData['id']);

                    if (!$variant) {
                        throw new \Exception("Variant ID {$variantData['id']} không được tìm thấy.");
                    }

                    // Cập nhật thông tin và set is_active = 1
                    $variant->update($variantPayload);
                    $variant->attributeValues()->sync($attributeValues);
                }
            }

            // 3. Tính toán lại stock và price cho SẢN PHẨM CHÍNH
            // Dựa trên các biến thể ACTIVE
            $activeVariants = $product->variants()->where('is_active', 1)->get();
            
            $totalStock = $activeVariants->sum('stock');
            $minPrice = $activeVariants->min('price');

            // Nếu không có variant nào active, set stock=0
            // và lấy giá min của tất cả (kể cả inactive) để hiển thị "Giá từ..."
            if ($activeVariants->isEmpty()) {
                $totalStock = 0;
                $minPrice = $product->variants()->min('price') ?? 0; 
            }
            
            $product->stock = $totalStock;
            $product->price = $minPrice;
            $product->save();

            $this->handleImageUpdate($request, $product);

            DB::commit();
            return redirect()->route('admin.products.edit', $product)
                ->with('success', 'Sản phẩm đã được cập nhật thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi khi cập nhật sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Vô hiệu hóa sản phẩm
     */
    public function destroy(Product $product)
    {
        $reservedStockCount = $product->variants()
            ->where('reservedStock', '>', 0)
            ->count();

        if ($reservedStockCount > 0) {
            return redirect()->back()
                ->with('error', 'Không thể vô hiệu hóa sản phẩm này. Có đơn hàng đang tạm giữ tồn kho (reserved stock).');
        }

        try {
            DB::beginTransaction();

            $product->is_active = 0;
            $product->save();
            
            // Tùy chọn: Cũng vô hiệu hóa tất cả các biến thể của nó
            // $product->variants()->update(['is_active' => 0]);

            DB::commit();

            return redirect()->route('admin.products.index', ['status' => 'active'])
                ->with('success', 'Sản phẩm "' . $product->productName . '" đã được VÔ HIỆU HÓA thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Lỗi khi vô hiệu hóa sản phẩm: ' . $e->getMessage());
        }
    }

    /**
     * Xóa ảnh (Ajax)
     */
    public function deleteImage(Product $product, $imageID)
    {
        $image = ProductImage::where('productID', $product->productID)
            ->where('imageID', $imageID)
            ->first();

        if (!$image) {
            return response()->json([
                'success' => false, 
                'message' => 'Ảnh không tìm thấy hoặc không thuộc về sản phẩm này.'
            ], 404);
        }

        try {
            Storage::disk('public')->delete($image->imageUrl);
            Storage::disk('api_legacy_uploads')->delete($image->imageUrl);

            $image->delete();
            return response()->json([
                'success' => true, 
                'message' => 'Ảnh đã được xóa thành công.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Lỗi khi xóa ảnh: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy thuộc tính theo category (Ajax)
     */
    public function getAttributesByCategory($categoryID)
    {
        $categoryAttributes = DB::table('category_attributes as ca')
            ->join('product_attributes as pa', 'ca.attributeID', '=', 'pa.attributeID')
            ->where('ca.categoryID', $categoryID)
            ->select('pa.attributeID', 'pa.attributeName', 'ca.valueID_start', 'ca.valueID_end')
            ->get();

        $result = [];
        
        foreach ($categoryAttributes as $catAttr) {
            $valuesQuery = DB::table('product_attribute_values')
                ->where('attributeID', $catAttr->attributeID);

            // Lọc theo range nếu có
            if ($catAttr->valueID_start && $catAttr->valueID_end) {
                $valuesQuery->whereBetween('valueID', [
                    $catAttr->valueID_start,
                    $catAttr->valueID_end
                ]);
            }

            $values = $valuesQuery->orderBy('valueID')->get();

            $result[] = [
                'attributeID' => $catAttr->attributeID,
                'attributeName' => $catAttr->attributeName,
                'values' => $values->map(function($v) {
                    return [
                        'valueID' => $v->valueID,
                        'valueName' => $v->valueName
                    ];
                })
            ];
        }

        return response()->json($result);
    }

    /**
     * Helper: Lấy mapping category -> attributes từ DB
     */
    protected function getCategoryAttributesMapping()
    {
        $mapping = [];
        
        $categoryAttributes = DB::table('category_attributes as ca')
            ->select('ca.categoryID', 'ca.attributeID', 'ca.valueID_start', 'ca.valueID_end')
            ->get()
            ->groupBy('categoryID');

        foreach ($categoryAttributes as $categoryID => $attrs) {
            $mapping[$categoryID] = [
                'attributes' => $attrs->pluck('attributeID')->toArray(),
                'filters' => []
            ];

            foreach ($attrs as $attr) {
                if ($attr->valueID_start && $attr->valueID_end) {
                    $allowedValues = DB::table('product_attribute_values')
                        ->where('attributeID', $attr->attributeID)
                        ->whereBetween('valueID', [$attr->valueID_start, $attr->valueID_end])
                        ->pluck('valueID')
                        ->toArray();
                    
                    $mapping[$categoryID]['filters'][$attr->attributeID] = $allowedValues;
                }
            }
        }

        return $mapping;
    }

    /**
     * Upload ảnh mới
     */
    protected function handleImageUpload(Request $request, Product $product)
    {
        $images = $request->file('images');
        $sortOrder = 1;

        $cleanFileName = function ($file) use ($product) {
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext = $file->getClientOriginalExtension();
            $productSlug = Str::slug($product->productName);
            return $productSlug . '-' . time() . '-' . Str::random(4) . '.' . $ext;
        };

        // Ảnh chính
        if (isset($images['main'])) {
            $fileName = $cleanFileName($images['main']);

            $images['main']->storeAs(self::FOLDER_PATH, $fileName, 'public');
            $images['main']->storeAs(self::FOLDER_PATH, $fileName, 'api_legacy_uploads');

            $product->images()->create([
                'imageUrl' => self::FOLDER_PATH . '/' . $fileName,
                'imageType' => 'main',
                'sortOrder' => 1,
            ]);
            $sortOrder = 2;
        }

        // Ảnh gallery
        if (isset($images['gallery']) && is_array($images['gallery'])) {
            foreach ($images['gallery'] as $galleryImage) {
                $fileName = $cleanFileName($galleryImage);

                $galleryImage->storeAs(self::FOLDER_PATH, $fileName, 'public');
                $galleryImage->storeAs(self::FOLDER_PATH, $fileName, 'api_legacy_uploads');

                $product->images()->create([
                    'imageUrl' => self::FOLDER_PATH . '/' . $fileName,
                    'imageType' => 'gallery',
                    'sortOrder' => $sortOrder++,
                ]);
            }
        }
    }

    /**
     * Cập nhật ảnh
     */
    protected function handleImageUpdate(Request $request, Product $product)
    {
        $images = $request->file('images');

        $cleanFileName = function ($file) use ($product) {
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext = $file->getClientOriginalExtension();
            $productSlug = Str::slug($product->productName);
            return $productSlug . '-' . time() . '-' . Str::random(4) . '.' . $ext;
        };

        // Thay thế ảnh chính
        if (isset($images['main'])) {
            $product->loadMissing('images');
            $mainImage = $product->images->where('imageType', 'main')->first();

            if ($mainImage) {
                Storage::disk('public')->delete($mainImage->imageUrl);
                Storage::disk('api_legacy_uploads')->delete($mainImage->imageUrl);
                $mainImage->delete();
            }

            $fileName = $cleanFileName($images['main']);

            $images['main']->storeAs(self::FOLDER_PATH, $fileName, 'public');
            $images['main']->storeAs(self::FOLDER_PATH, $fileName, 'api_legacy_uploads');

            $product->images()->create([
                'imageUrl' => self::FOLDER_PATH . '/' . $fileName,
                'imageType' => 'main',
                'sortOrder' => 1,
            ]);
        }

        // Thêm ảnh gallery mới
        if (isset($images['gallery']) && is_array($images['gallery'])) {
            $product->loadMissing('images');
            $sortOrder = $product->images->max('sortOrder') ?? 0;

            if ($product->images->contains('imageType', 'main') && $sortOrder < 2) {
                $sortOrder = 1;
            }
            $sortOrder++;

            foreach ($images['gallery'] as $galleryImage) {
                $fileName = $cleanFileName($galleryImage);

                $galleryImage->storeAs(self::FOLDER_PATH, $fileName, 'public');
                $galleryImage->storeAs(self::FOLDER_PATH, $fileName, 'api_legacy_uploads');

                $product->images()->create([
                    'imageUrl' => self::FOLDER_PATH . '/' . $fileName,
                    'imageType' => 'gallery',
                    'sortOrder' => $sortOrder++,
                ]);
            }
        }
    }
}