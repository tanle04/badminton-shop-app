<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'categoryID';
    public $timestamps = false;

    protected $fillable = [
        'categoryName',
    ];

    /**
     * Relationship: Một category có nhiều products
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'categoryID', 'categoryID');
    }

    /**
     * Relationship: Một category có nhiều attributes
     * Qua bảng trung gian category_attributes
     */
    public function attributes()
    {
        return $this->belongsToMany(
            ProductAttribute::class,
            'category_attributes',
            'categoryID',
            'attributeID'
        )->withPivot('valueID_start', 'valueID_end');
    }

    /**
     * Lấy tất cả giá trị của một attribute cho category này
     * Có tính đến giới hạn range nếu có
     * 
     * @param int $attributeID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAttributeValues($attributeID)
    {
        $pivot = $this->attributes()
            ->where('attributeID', $attributeID)
            ->first();

        if (!$pivot) {
            return collect();
        }

        $query = ProductAttributeValue::where('attributeID', $attributeID);

        // Nếu có giới hạn range trong pivot
        if ($pivot->pivot->valueID_start && $pivot->pivot->valueID_end) {
            $query->whereBetween('valueID', [
                $pivot->pivot->valueID_start,
                $pivot->pivot->valueID_end
            ]);
        }

        return $query->orderBy('valueID')->get();
    }
}