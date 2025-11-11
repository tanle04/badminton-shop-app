<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundRequestItem extends Model
{
    use HasFactory;

    protected $table = 'refund_request_items';
    protected $primaryKey = 'refundItemID';
    public $timestamps = false;

    protected $fillable = [
        'refundRequestID',
        'orderDetailID',
        'quantity',
        'reason'
    ];

    public function refundRequest()
    {
        return $this->belongsTo(RefundRequest::class, 'refundRequestID', 'refundRequestID');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'orderDetailID', 'orderDetailID');
    }
}