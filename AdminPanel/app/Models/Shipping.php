<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    use HasFactory;

    protected $table = 'shipping'; // Tên bảng trong DB
    protected $primaryKey = 'shippingID';
    public $timestamps = false;
    
    protected $fillable = [
        'orderID', 
        'shippingMethod', 
        'shippingFee', 
        'trackingCode', 
        'shippedDate'
    ];

    /**
     * Mối quan hệ: Vận chuyển thuộc về một Đơn hàng.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }
}