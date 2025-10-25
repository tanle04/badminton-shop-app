<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $primaryKey = 'categoryID'; // Khóa chính tùy chỉnh
    public $timestamps = false; // Tắt timestamps
    protected $guarded = []; // Cho phép gán hàng loạt

    public function products()
    {
        // Quan hệ 1-nhiều với Product
        return $this->hasMany(Product::class, 'categoryID', 'categoryID');
    }
}