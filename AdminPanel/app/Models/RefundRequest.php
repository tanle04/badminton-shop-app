<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class RefundRequest extends Model
{
    use HasFactory;

    protected $table = 'refund_requests';
    protected $primaryKey = 'refundRequestID';
    public $timestamps = false;

    protected $fillable = [
        'orderID',
        'customerID',
        'reason',
        'status',
        'requestDate',
        'adminNotes'
    ];

    protected $casts = [
        'requestDate' => 'datetime',
    ];

    /**
     * Relationship: RefundRequest belongs to Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'orderID', 'orderID');
    }

    /**
     * Relationship: RefundRequest belongs to Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID', 'customerID');
    }

    /**
     * Relationship: RefundRequest has many Items
     */
    public function items()
    {
        return $this->hasMany(RefundRequestItem::class, 'refundRequestID', 'refundRequestID');
    }

    /**
     * Relationship: RefundRequest has many Media (photos/videos)
     */
    public function media()
    {
        return $this->hasMany(RefundRequestMedia::class, 'refundRequestID', 'refundRequestID');
    }
    
    /**
     * Get only photos
     */
    public function photos()
    {
        return $this->media()->where('mediaType', 'photo');
    }
    
    /**
     * Get only videos
     */
    public function videos()
    {
        return $this->media()->where('mediaType', 'video');
    }
}