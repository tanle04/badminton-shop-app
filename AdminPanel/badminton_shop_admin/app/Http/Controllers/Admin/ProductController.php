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
use Illuminate\Support\Str; // <-- Cần cho Str::slug

class ProductController extends Controller
{
    // Folder con để tổ chức files. Cần khớp với tên folder trong DB và API gốc
    const FOLDER_PATH = 'products';

    // Triển khai phương thức Index (Đã có)
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

    // Triển khai phương thức Create (Đã có)
    public function create()
    {
        $categories = Category::all();
        $brands = Brand::all();
        $attributes = ProductAttribute::with('values')->get();

        return view('admin.products.create', compact('categories', 'brands', 'attributes'));
    }

    /**
     * LƯU TRỮ SẢN PHẨM MỚI - Đồng bộ Tệp và Tắt Hash.
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
            ]);

            foreach ($request->variants as $variantData) {
                $variant = $product->variants()->create([
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'reservedStock' => 0,
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

            // SỬ DỤNG HÀM MỚI
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

    // Triển khai phương thức Edit (Đã có)
    public function edit(Product $product)
    {
        $product->load([
            'category',
            'brand',
            'variants.attributeValues',
            'images'
        ]);

        $categories = Category::all();
        $brands = Brand::all();
        $attributes = ProductAttribute::with('values')->get();

        return view('admin.products.edit', compact(
            'product',
            'categories',
            'brands',
            'attributes'
        ));
    }

    /**
     * CẬP NHẬT SẢN PHẨM ĐÃ TỒN TẠI - Đồng bộ Tệp và Tắt Hash.
     */
    public function update(Request $request, Product $product)
    {
        // LOGIC KÍCH HOẠT LẠI:
        if ($request->has('action_reactivate')) {
            $product->is_active = 1;
            $product->save();
            return redirect()->route('admin.products.index', ['status' => 'active'])
                ->with('success', 'Sản phẩm "' . $product->productName . '" đã được KÍCH HOẠT LẠI thành công!');
        }

        // ... (Validation đã có) ...
        $request->validate([
            'productName' => 'required|string|max:255',
            'categoryID' => 'required|exists:categories,categoryID',
            'brandID' => 'required|exists:brands,brandID',
            'images.main' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'variants' => 'required|array',
            'variants.*.id' => 'required|exists:product_variants,variantID',
            'variants.*.price' => 'required|numeric|min:1000',
            'variants.*.stock' => 'required|integer|min:0',
            'selected_attribute_values' => 'nullable|array',
            'variants.*.sku' => [
                'required',
                'max:50',
                function ($attribute, $value, $fail) use ($request, $product) {
                    $index = explode('.', $attribute)[1];
                    $variantId = $request->variants[$index]['id'] ?? null;
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
            // ... (Logic cập nhật Sản phẩm, Variants, Thuộc tính đã có) ...

            $product->update([
                'productName' => $request->productName,
                'description' => $request->description,
                'categoryID' => $request->categoryID,
                'brandID' => $request->brandID,
            ]);

            $totalStock = 0;
            $minPrice = PHP_INT_MAX;

            foreach ($request->variants as $variantData) {
                $variant = $product->variants()->where('variantID', $variantData['id'])->firstOrFail();
                $newStock = $variantData['stock'];
                $variant->update([
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'stock' => $newStock,
                ]);

                $totalStock += $newStock;
                $minPrice = min($minPrice, $variant->price);
            }
            $firstVariant = $product->variants->first();
            if ($firstVariant && $request->has('selected_attribute_values')) {
                $firstVariant->attributeValues()->sync($request->selected_attribute_values);
            }

            $product->stock = $totalStock;
            $product->price = $product->variants()->min('price');
            $product->save();

            // SỬ DỤNG HÀM MỚI
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

    // Triển khai phương thức Destroy (Đã có)
    /**
     * Vô hiệu hóa (Xóa mềm) sản phẩm bằng cách đặt is_active = 0.
     * @param Product $product
     */
    public function destroy(Product $product)
    {
        // 1. Kiểm tra xem sản phẩm có đang bị giữ bởi Reserved Stock không
        // Nếu có bất kỳ biến thể nào đang có reservedStock > 0, không cho phép xóa
        $reservedStockCount = $product->variants()
            ->where('reservedStock', '>', 0)
            ->count();

        if ($reservedStockCount > 0) {
            return redirect()->back()
                ->with('error', 'Không thể vô hiệu hóa sản phẩm này. Có đơn hàng đang tạm giữ tồn kho (reserved stock).');
        }

        try {
            DB::beginTransaction();

            // 2. Vô hiệu hóa sản phẩm (Xóa mềm)
            $product->is_active = 0;
            $product->save();

            DB::commit();

            return redirect()->route('admin.products.index', ['status' => 'active'])
                ->with('success', 'Sản phẩm "' . $product->productName . '" đã được VÔ HIỆU HÓA thành công.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Lỗi khi vô hiệu hóa sản phẩm: ' . $e->getMessage());
        }
    }
    // ...

    // Triển khai phương thức deleteImage (Cho Ajax)
    public function deleteImage(Product $product, $imageID)
    {
        $image = ProductImage::where('productID', $product->productID)
            ->where('imageID', $imageID)
            ->first();

        if (!$image) {
            return response()->json(['success' => false, 'message' => 'Ảnh không tìm thấy hoặc không thuộc về sản phẩm này.'], 404);
        }

        try {
            // Xóa khỏi Admin Panel Storage (disk: public)
            Storage::disk('public')->delete($image->imageUrl);
            // Xóa khỏi API Client Storage (disk: api_legacy_uploads)
            Storage::disk('api_legacy_uploads')->delete($image->imageUrl);

            $image->delete();
            return response()->json(['success' => true, 'message' => 'Ảnh đã được xóa thành công.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Lỗi khi xóa ảnh: ' . $e->getMessage()], 500);
        }
    }

    // --- HÀM PHỤ TRỢ: DUAL SAVE VÀ TẮT HASH ---

    /**
     * Hàm phụ trợ xử lý upload hình ảnh (Lưu vào 2 nơi và Giữ tên gốc).
     */
    protected function handleImageUpload(Request $request, Product $product)
    {
        $images = $request->file('images');
        $sortOrder = 1;

        // Hàm làm sạch tên file (Giữ tên gốc + thêm timestamp + slug)
        $cleanFileName = function ($file) use ($product) {
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $ext = $file->getClientOriginalExtension();
            $productSlug = Str::slug($product->productName);
            // Cấu trúc: products/ten-san-pham-timestamp-random.ext
            return $productSlug . '-' . time() . '-' . Str::random(4) . '.' . $ext;
        };

        // 6.1. Xử lý Ảnh Chính (Main Image)
        if (isset($images['main'])) {
            $fileName = $cleanFileName($images['main']);

            // A. Lưu vào Admin Panel Storage (chuẩn Laravel, dùng disk 'public')
            $images['main']->storeAs(self::FOLDER_PATH, $fileName, 'public');

            // B. Lưu vào API Client Storage (thư mục cũ)
            $images['main']->storeAs(self::FOLDER_PATH, $fileName, 'api_legacy_uploads');

            // C. Lưu vào DB: Main image luôn là sortOrder = 1
            $product->images()->create([
                'imageUrl' => self::FOLDER_PATH . '/' . $fileName,
                'imageType' => 'main',
                'sortOrder' => 1,
            ]);
            $sortOrder = 2; // Chuẩn bị cho ảnh gallery
        }

        // 6.2. Xử lý Ảnh Thư viện (Gallery Images)
        if (isset($images['gallery']) && is_array($images['gallery'])) {
            foreach ($images['gallery'] as $galleryImage) {
                $fileName = $cleanFileName($galleryImage);

                // A. Lưu vào Admin Panel Storage
                $galleryImage->storeAs(self::FOLDER_PATH, $fileName, 'public');

                // B. Lưu vào API Client Storage
                $galleryImage->storeAs(self::FOLDER_PATH, $fileName, 'api_legacy_uploads');

                // C. Lưu vào DB
                $product->images()->create([
                    'imageUrl' => self::FOLDER_PATH . '/' . $fileName,
                    'imageType' => 'gallery',
                    'sortOrder' => $sortOrder++,
                ]);
            }
        }
    }

    /**
     * Hàm phụ trợ xử lý cập nhật hình ảnh (Lưu vào 2 nơi và Tắt Hash).
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

        // 6.1. Thay thế Ảnh Chính (Main Image)
        if (isset($images['main'])) {
            $product->loadMissing('images');
            $mainImage = $product->images->where('imageType', 'main')->first();

            // Xóa ảnh cũ khỏi cả hai disks
            if ($mainImage) {
                Storage::disk('public')->delete($mainImage->imageUrl);
                Storage::disk('api_legacy_uploads')->delete($mainImage->imageUrl);
                $mainImage->delete();
            }

            $fileName = $cleanFileName($images['main']);

            // Lưu ảnh mới vào cả hai disks
            $images['main']->storeAs(self::FOLDER_PATH, $fileName, 'public');
            $images['main']->storeAs(self::FOLDER_PATH, $fileName, 'api_legacy_uploads');

            // Thêm bản ghi mới (sortOrder luôn là 1)
            $product->images()->create([
                'imageUrl' => self::FOLDER_PATH . '/' . $fileName,
                'imageType' => 'main',
                'sortOrder' => 1,
            ]);
        }

        // 6.2. Thêm Ảnh Thư viện mới (Gallery Images)
        if (isset($images['gallery']) && is_array($images['gallery'])) {
            $product->loadMissing('images');
            // Lấy max sortOrder hiện tại (bao gồm cả ảnh main) để tiếp tục
            $sortOrder = $product->images->max('sortOrder') ?? 0;

            // Nếu không có ảnh main mới, và ảnh main cũ có sortOrder=1, $sortOrder có thể là 1
            // Cần đảm bảo sortOrder bắt đầu từ 2 nếu main image tồn tại.
            if ($product->images->contains('imageType', 'main') && $sortOrder < 2) {
                $sortOrder = 1;
            }
            $sortOrder++;

            foreach ($images['gallery'] as $galleryImage) {
                $fileName = $cleanFileName($galleryImage);

                // Lưu vào cả hai disks
                $galleryImage->storeAs(self::FOLDER_PATH, $fileName, 'public');
                $galleryImage->storeAs(self::FOLDER_PATH, $fileName, 'api_legacy_uploads');

                // Thêm bản ghi mới
                $product->images()->create([
                    'imageUrl' => self::FOLDER_PATH . '/' . $fileName,
                    'imageType' => 'gallery',
                    'sortOrder' => $sortOrder++,
                ]);
            }
        }
    }
}
