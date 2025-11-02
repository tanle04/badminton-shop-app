<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'customerID';
    
    protected $fillable = [
        'fullName',
        'email',
        'password_hash',
        'phone',
        'isEmailVerified',
        'verificationToken',
        'tokenExpiry',
        'img_url',
    ];
    
    protected $hidden = [
        'password_hash',
        'verificationToken',
    ];
    
    protected $casts = [
        'isEmailVerified' => 'boolean',
        'tokenExpiry' => 'datetime',
    ];
    
    // ✅ FIX: Không dùng backticks
    const CREATED_AT = 'createdDate';
    const UPDATED_AT = null;
    
    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================
    
    public function supportConversations()
    {
        return $this->hasMany(SupportConversation::class, 'customer_id', 'customerID');
    }
    
    public function supportMessages()
    {
        return $this->hasMany(SupportMessage::class, 'sender_id', 'customerID')
            ->where('sender_type', 'customer');
    }
}