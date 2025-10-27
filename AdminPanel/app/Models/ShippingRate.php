<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasFactory;

    protected $table = 'shipping_rates';
    protected $primaryKey = 'rateID';
    public $timestamps = false; // Bảng này không có created_at, updated_at

    protected $fillable = [
        'carrierID',
        'serviceName',
        'price',
        'estimatedDelivery'
    ];

    /**
     * Mối quan hệ: Một Rate thuộc về một Carrier.
     */
    public function carrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'carrierID', 'carrierID');
    }
}
