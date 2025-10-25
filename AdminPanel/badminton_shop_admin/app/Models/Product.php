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
        'brandID'
    ];
    protected $casts = [
        'is_active' => 'boolean', // <--- Đảm bảo nó được coi là boolean
    ];
    
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