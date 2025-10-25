<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $table = 'product_attribute_values';
    protected $primaryKey = 'valueID';
    public $timestamps = false;
    protected $fillable = ['attributeID', 'valueName'];

    // Mối quan hệ: Giá trị thuộc về một Thuộc tính (Ví dụ: 'M' thuộc về 'Size')
    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'attributeID', 'attributeID');
    }

    // Mối quan hệ Many-to-Many: Giá trị được áp dụng cho nhiều Biến thể
    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class, 
            'variant_attribute_values', // Tên bảng trung gian
            'valueID', 
            'variantID'
        );
    }
}