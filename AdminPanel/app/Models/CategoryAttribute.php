<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAttribute extends Model
{
    protected $table = 'category_attributes';
    protected $primaryKey = 'category_attribute_id';
    public $timestamps = false;
    protected $fillable = ['categoryID', 'attributeID', 'valueID_start', 'valueID_end'];
    
    // Đảm bảo các thuộc tính là số nguyên để truy vấn
    protected $casts = [
        'categoryID' => 'integer',
        'attributeID' => 'integer',
        'valueID_start' => 'integer',
        'valueID_end' => 'integer',
    ];

    // Quan hệ với ProductAttribute
    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'attributeID', 'attributeID');
    }
}