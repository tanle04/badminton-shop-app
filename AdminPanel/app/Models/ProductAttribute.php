<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $table = 'product_attributes';
    protected $primaryKey = 'attributeID';
    public $timestamps = false;

    protected $fillable = [
        'attributeName',
    ];

    /**
     * Relationship: Một attribute có nhiều values
     */
    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'attributeID', 'attributeID');
    }

    /**
     * Relationship: Một attribute thuộc về nhiều categories
     * Qua bảng trung gian category_attributes
     */
    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'category_attributes',
            'attributeID',
            'categoryID'
        )->withPivot('valueID_start', 'valueID_end');
    }

    /**
     * Lấy các giá trị phù hợp cho một category cụ thể
     * 
     * @param int $categoryID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getValuesByCategoryAttribute($categoryID)
    {
        $pivot = $this->categories()
            ->where('categoryID', $categoryID)
            ->first();

        if (!$pivot) {
            return collect();
        }

        $query = $this->values();

        // Nếu có giới hạn range trong pivot
        if ($pivot->pivot->valueID_start && $pivot->pivot->valueID_end) {
            $query->whereBetween('valueID', [
                $pivot->pivot->valueID_start,
                $pivot->pivot->valueID_end
            ]);
        }

        return $query->orderBy('valueID')->get();
    }

    /**
     * Kiểm tra xem attribute có đang được sử dụng không
     * 
     * @return bool
     */
    public function isInUse()
    {
        return \DB::table('variant_attribute_values as vav')
            ->join('product_attribute_values as pav', 'vav.valueID', '=', 'pav.valueID')
            ->where('pav.attributeID', $this->attributeID)
            ->exists();
    }

    /**
     * Đếm số variant đang sử dụng attribute này
     * 
     * @return int
     */
    public function getUsageCount()
    {
        return \DB::table('variant_attribute_values as vav')
            ->join('product_attribute_values as pav', 'vav.valueID', '=', 'pav.valueID')
            ->where('pav.attributeID', $this->attributeID)
            ->distinct('vav.variantID')
            ->count('vav.variantID');
    }
}