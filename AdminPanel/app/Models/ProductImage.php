<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $table = 'productimages';
    protected $primaryKey = 'imageID';
    public $timestamps = false;
    protected $fillable = ['productID', 'imageUrl', 'imageType', 'sortOrder'];

    // Mối quan hệ: Hình ảnh thuộc về một Sản phẩm
    public function product()
    {
        return $this->belongsTo(Product::class, 'productID', 'productID');
    }
}