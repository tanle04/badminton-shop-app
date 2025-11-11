<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $primaryKey = 'orderID';
    public $timestamps = false;

    protected $fillable = [
        'customerID',
        'addressID',
        'orderDate',
        'paymentMethod',
        'paymentStatus',
        'status',
        'total',
        'voucherID',
        'paymentExpiry',
        'paymentToken',
    ];

    protected $casts = [
        'orderDate' => 'datetime',
        'paymentExpiry' => 'datetime',
        'total' => 'decimal:2',
    ];

    /**
     * Quan hệ với Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID', 'customerID');
    }

    /**
     * Quan hệ với CustomerAddress
     */
    public function address()
    {
        return $this->belongsTo(CustomerAddress::class, 'addressID', 'addressID');
    }

    /**
     * Quan hệ với OrderDetails
     */
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'orderID', 'orderID');
    }

    /**
     * Quan hệ với Voucher
     */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucherID', 'voucherID');
    }

    /**
     * Quan hệ với Shipping
     */
    public function shipping()
    {
        return $this->hasOne(Shipping::class, 'orderID', 'orderID');
    }

    /**
     * Relationship: Order has many RefundRequests
     */
    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class, 'orderID', 'orderID');
    }

    /**
     * Get latest pending refund request
     */
    public function pendingRefundRequest()
    {
        return $this->hasOne(RefundRequest::class, 'orderID', 'orderID')
                    ->where('status', 'Pending')
                    ->latest('requestDate');
    }
}
