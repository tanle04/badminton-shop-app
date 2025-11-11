<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundRequestMedia extends Model
{
    use HasFactory;

    protected $table = 'refund_request_media';
    protected $primaryKey = 'mediaID';
    public $timestamps = false;

    protected $fillable = [
        'refundRequestID',
        'mediaUrl',
        'mediaType'
    ];

    public function refundRequest()
    {
        return $this->belongsTo(RefundRequest::class, 'refundRequestID', 'refundRequestID');
    }
}