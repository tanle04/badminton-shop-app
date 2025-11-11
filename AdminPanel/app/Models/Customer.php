<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'customers';
    protected $primaryKey = 'customerID';
    public $timestamps = false;

    protected $fillable = [
        'fullName',
        'email',
        'password_hash',
        'isEmailVerified',
        'verificationToken',
        'tokenExpiry',
        'phone',
        'is_active',
        'createdDate',
    ];

    protected $hidden = [
        'password_hash',
        'verificationToken',
    ];

    protected $casts = [
        'isEmailVerified' => 'boolean',
        'is_active' => 'boolean',
        'createdDate' => 'datetime',
        'tokenExpiry' => 'datetime',
    ];

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Quan hệ với CustomerAddress
     */
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customerID', 'customerID');
    }

    /**
     * Lấy địa chỉ mặc định
     */
    public function defaultAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'customerID', 'customerID')
                    ->where('isDefault', 1)
                    ->where('is_active', 1);
    }

    /**
     * Quan hệ với Orders
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customerID', 'customerID');
    }

    /**
     * Quan hệ với Reviews (nếu có bảng reviews)
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'customerID', 'customerID');
    }

    /**
     * Quan hệ với CustomerVouchers (nếu có bảng customer_vouchers)
     */
    public function customerVouchers()
    {
        return $this->hasMany(CustomerVoucher::class, 'customerID', 'customerID');
    }

    /**
     * Quan hệ với SupportConversations (nếu có bảng support_conversations)
     */
    public function supportConversations()
    {
        return $this->hasMany(SupportConversation::class, 'customer_id', 'customerID');
    }

    /**
     * Quan hệ với SupportMessages (nếu có bảng support_messages)
     */
    public function supportMessages()
    {
        return $this->hasMany(SupportMessage::class, 'sender_id', 'customerID')
                    ->where('sender_type', 'customer');
    }
}
