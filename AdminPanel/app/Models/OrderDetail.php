<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;

    protected $table = 'orderdetails';
    protected $primaryKey = 'orderDetailID';
    public $timestamps = false;
    
    protected $fillable = [
        'orderID', 'variantID', 'quantity', 'price', 'isReviewed'
    ];

    // Mối quan hệ: Chi tiết đơn hàng thuộc về một Đơn hàng
    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }

    // Mối quan hệ: Chi tiết đơn hàng liên quan đến một Biến thể sản phẩm
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variantID', 'variantID');
    }
}