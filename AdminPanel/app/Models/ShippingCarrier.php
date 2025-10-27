<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingCarrier extends Model
{
    use HasFactory;

    protected $table = 'shipping_carriers';
    protected $primaryKey = 'carrierID';
    public $timestamps = false; // Bảng này không có created_at, updated_at

    protected $fillable = [
        'carrierName',
        'isActive'
    ];

    /**
     * Mối quan hệ: Một Carrier có nhiều Rates (mức phí).
     */
    public function rates()
    {
        return $this->hasMany(ShippingRate::class, 'carrierID', 'carrierID');
    }
}
