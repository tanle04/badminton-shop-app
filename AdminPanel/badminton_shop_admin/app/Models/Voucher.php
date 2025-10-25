<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $table = 'vouchers';
    protected $primaryKey = 'voucherID';
    // Bảng vouchers không dùng created_at/updated_at mặc định
    public $timestamps = false; 
    
    // Các trường được phép gán hàng loạt (Mass Assignment)
    protected $fillable = [
        'voucherCode', 
        'description', 
        'discountType', 
        'discountValue', 
        'minOrderValue', 
        'maxDiscountAmount', 
        'maxUsage', 
        'usedCount', 
        'startDate', 
        'endDate', 
        'isActive', 
        'isPrivate'
    ];

    // Chuyển đổi kiểu dữ liệu cho các trường ngày tháng và boolean
    protected $casts = [
        'startDate' => 'datetime',
        'endDate' => 'datetime',
        'isActive' => 'boolean',
        'isPrivate' => 'boolean',
        'discountValue' => 'decimal:2',
        'minOrderValue' => 'decimal:2',
        'maxDiscountAmount' => 'decimal:2',
    ];
    
    /*
     * Mối quan hệ: Một Voucher có thể được áp dụng cho nhiều đơn hàng.
     * Mặc dù bảng Orders có cột voucherID, nhưng không cần định nghĩa mối quan hệ ở đây
     * trừ khi bạn muốn truy vấn ngược lại (Voucher -> Orders).
     */
}