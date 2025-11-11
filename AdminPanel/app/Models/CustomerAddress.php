<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;

    protected $table = 'customer_addresses';
    protected $primaryKey = 'addressID';
    public $timestamps = false;

    protected $fillable = [
        'customerID',
        'recipientName',
        'phone',
        'street',
        'city',
        'postalCode',
        'country',
        'isDefault',
        'is_active',
    ];

    protected $casts = [
        'isDefault' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Quan hệ với Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customerID', 'customerID');
    }

    /**
     * Quan hệ với Orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'addressID', 'addressID');
    }
}
