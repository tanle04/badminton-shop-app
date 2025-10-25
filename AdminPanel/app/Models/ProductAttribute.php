<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $table = 'product_attributes';
    protected $primaryKey = 'attributeID';
    public $timestamps = false;
    protected $fillable = ['attributeName'];
    
    // Mối quan hệ: Một Thuộc tính có nhiều Giá trị (Ví dụ: Size có các giá trị M, L, XL)
    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'attributeID', 'attributeID');
    }
}