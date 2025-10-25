<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $table = 'product_variants';
    protected $primaryKey = 'variantID';
    public $timestamps = false; // Không có timestamps
    
    protected $fillable = [
        'productID', 
        'sku', 
        'price', 
        'stock',
        'reservedStock'
    ];

    // Mối quan hệ: Biến thể thuộc về một Sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class, 'productID', 'productID');
    }

    // Mối quan hệ Many-to-Many: Biến thể có nhiều Giá trị Thuộc tính (Size, G5, 4U,...)
    public function attributeValues()
    {
        return $this->belongsToMany(
            ProductAttributeValue::class, 
            'variant_attribute_values', // Tên bảng trung gian
            'variantID',                // Khóa ngoại của model hiện tại trong bảng trung gian
            'valueID'                   // Khóa ngoại của model đích trong bảng trung gian
        );
    }
}