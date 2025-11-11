<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// ⭐ Đảm bảo bạn đã import 2 model này
use App\Models\ProductAttributeValue; 
use App\Models\CategoryAttribute;

class Category extends Model
{
  protected $table = 'categories';
  protected $primaryKey = 'categoryID';
  public $timestamps = false;

  protected $fillable = [
    'categoryName',
    'is_active', // ⭐ (Đã có từ lần trước)
  ];

  /**
  * Relationship: Một category có nhiều products (chỉ SP active)
  */
  public function products()
  {
    return $this->hasMany(Product::class, 'categoryID', 'categoryID')
          ->where('is_active', 1); 
  }
  
  /**
  * Lấy TẤT CẢ sản phẩm (bao gồm cả sản phẩm đã ẩn)
  */
  public function allProducts()
  {
    return $this->hasMany(Product::class, 'categoryID', 'categoryID');
  }

  /**
  * Relationship: Một category có nhiều attributes
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
  * Quan hệ để lấy bản ghi pivot category_attributes
  */
  public function categoryAttributes()
  {
    return $this->hasMany(CategoryAttribute::class, 'categoryID', 'categoryID');
  }

  /**
  * Lấy tất cả giá trị của một attribute cho category này
  *
  * @param int $attributeID
  * @return \Illuminate\Database\Eloquent\Collection
  */
  public function getAttributeValues($attributeID)
  {
    $pivot = $this->attributes()
            // ⭐ SỬA LỖI 2: Chỉ định rõ bảng 'product_attributes.attributeID'
      ->where('product_attributes.attributeID', $attributeID) 
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
