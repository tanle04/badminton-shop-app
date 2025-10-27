<?php
// app/Models/ProductDiscount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDiscount extends Model
{
    use HasFactory;

    protected $table = 'product_discounts'; // Đảm bảo tên bảng chính xác
    protected $primaryKey = 'discountID';
    
    // THÊM DÒNG NÀY: Vô hiệu hóa tính năng tự động quản lý timestamps
    public $timestamps = false; 
    
    // Khai báo các trường có thể được gán giá trị hàng loạt
    protected $fillable = [
        'discountName',
        'discountType',
        'discountValue',
        'maxDiscountAmount',
        'startDate',
        'endDate',
        'appliedToType',
        'appliedToID',
        'isActive',
    ];

    // Ép kiểu cho các trường thời gian
    protected $casts = [
        'startDate' => 'datetime',
        'endDate' => 'datetime',
        'discountValue' => 'float',
        'maxDiscountAmount' => 'float',
        'isActive' => 'boolean',
    ];
}