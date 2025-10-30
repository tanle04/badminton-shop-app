<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    protected $table = 'product_attribute_values';
    protected $primaryKey = 'valueID';
    public $timestamps = false;
    protected $fillable = ['attributeID', 'valueName'];

    /**
     * Định nghĩa mối quan hệ n-1: Một giá trị thuộc về một thuộc tính.
     */
    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'attributeID', 'attributeID');
    }

    /**
     * Định nghĩa mối quan hệ n-n với ProductVariant thông qua bảng trung gian.
     */
    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'variant_attribute_values',
            'valueID',
            'variantID'
        );
    }
}