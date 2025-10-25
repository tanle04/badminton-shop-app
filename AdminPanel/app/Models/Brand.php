<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // Cần thiết cho Str::slug()

class Brand extends Model
{
    use HasFactory;

    // Tên bảng trong CSDL
    protected $table = 'brands';

    // Khóa chính trong CSDL là brandID
    protected $primaryKey = 'brandID'; 

    // Tắt timestamps (created_at, updated_at) vì bảng không có
    public $timestamps = false; 

    // Đảm bảo chỉ cho phép gán brandName hàng loạt
    protected $fillable = ['brandName']; 

    /**
     * Định nghĩa mối quan hệ One-to-Many: 
     * Một Brand có nhiều Products.
     * Khóa ngoại trong bảng products là 'brandID', khóa cục bộ là 'brandID'.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'brandID', 'brandID');
    }

    /* |--------------------------------------------------------------------------
    | ACCESSOR & MUTATOR
    |--------------------------------------------------------------------------
    | Các hàm này giúp Controller/View có thể sử dụng $brand->brandName thay vì name 
    | và tự động tạo/cập nhật slug nếu bạn cần thêm cột 'slug' vào CSDL sau này.
    */

    // // Tùy chọn: Tự động tạo slug (Đã comment vì CSDL hiện tại không có cột 'slug'
    // // Nếu bạn thêm cột slug vào CSDL, hãy bỏ comment phần code dưới đây.
    // protected static function boot()
    // {
    //     parent::boot();

    //     static::saving(function ($brand) {
    //         if (isset($brand->brandName)) {
    //             $brand->slug = Str::slug($brand->brandName);
    //         }
    //     });
    // }
}
