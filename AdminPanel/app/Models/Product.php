<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';
    protected $primaryKey = 'productID';
    
    // Mặc định `createdDate` là timestamp
    const CREATED_AT = 'createdDate'; 
    const UPDATED_AT = null; // Nếu không dùng updated_at
    
    protected $fillable = [
        'productName', 
        'description', 
        'price', 
        'stock', 
        'categoryID', 
        'brandID',
        'is_active', // Thêm is_active vào fillable để có thể update trực tiếp
    ];
    
    protected $casts = [
        'is_active' => 'boolean', // <--- Đảm bảo nó được coi là boolean
    ];
    
    // =========================================================================
    // PHƯƠNG THỨC CẬP NHẬT TỔNG HỢP (REQUIRED FOR ORDER CONTROLLER)
    // =========================================================================

    /**
     * Tính toán lại và cập nhật tổng stock và giá thấp nhất của sản phẩm
     * dựa trên tất cả các biến thể (variants) của nó.
     * * Hàm này được gọi trong ProductController@update và OrderController@update
     * sau khi stock của biến thể bị thay đổi.
     */
    public function updateStockAndPriceFromVariants()
    {
        // Tải lại variants để đảm bảo lấy dữ liệu stock và giá mới nhất
        $this->load('variants');
        
        // 1. Tính toán lại tổng Stock
        // Sử dụng sum() của Collection
        $totalStock = $this->variants->sum('stock'); 
        
        // 2. Tính toán lại giá thấp nhất
        // Sử dụng min() của Collection. Nếu không có variant nào, giá là 0.00
        $minPrice = $this->variants->min('price') ?? 0.00;

        // Cập nhật và lưu
        $this->stock = $totalStock;
        $this->price = $minPrice;
        $this->save();
    }

    // =========================================================================
    // MỐI QUAN HỆ (RELATIONSHIPS)
    // =========================================================================

    // Mối quan hệ: Sản phẩm thuộc về một Danh mục
    public function category()
    {
        return $this->belongsTo(Category::class, 'categoryID', 'categoryID');
    }

    // Mối quan hệ: Sản phẩm thuộc về một Thương hiệu
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brandID', 'brandID');
    }

    // Mối quan hệ: Một Sản phẩm có nhiều Biến thể (Variants)
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'productID', 'productID');
    }

    // Mối quan hệ: Một Sản phẩm có nhiều Hình ảnh
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'productID', 'productID')->orderBy('sortOrder');
    }

    // Mối quan hệ: Một Sản phẩm có nhiều Đánh giá
    public function reviews()
    {
        return $this->hasMany(Review::class, 'productID', 'productID');
    }
}