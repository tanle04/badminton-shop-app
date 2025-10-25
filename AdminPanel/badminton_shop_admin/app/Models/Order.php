<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $primaryKey = 'orderID';
    // Sử dụng cột orderDate làm timestamp tạo
    const CREATED_AT = 'orderDate'; 
    const UPDATED_AT = null;
    
    protected $fillable = [
        'customerID', 'addressID', 'orderDate', 'paymentMethod', 
        'paymentStatus', 'status', 'total', 'voucherID', 
        'paymentExpiry', 'paymentToken'
    ];
    
    // Mối quan hệ: Đơn hàng thuộc về một Khách hàng
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID', 'customerID');
    }

    // Mối quan hệ: Đơn hàng có nhiều Chi tiết đơn hàng
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'orderID', 'orderID');
    }

    // Mối quan hệ: Đơn hàng được giao đến một Địa chỉ
    public function address()
    {
        return $this->belongsTo(CustomerAddress::class, 'addressID', 'addressID');
    }
    
    // Mối quan hệ: Đơn hàng có thể áp dụng một Voucher
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucherID', 'voucherID');
    }
    public function shipping()
    {
        return $this->hasOne(Shipping::class, 'orderID', 'orderID');
    }
}