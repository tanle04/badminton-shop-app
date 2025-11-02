<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    protected $table = 'support_messages';
    
    protected $fillable = [
        'conversation_id',
        'sender_type',
        'sender_id',
        'message',
        'attachment_path',
        'attachment_name',
        'is_read',
        'read_at',
    ];
    
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // ============================================================================
    // RELATIONSHIPS - ✅ KHÔNG WHERE
    // ============================================================================
    
    public function conversation()
    {
        return $this->belongsTo(SupportConversation::class, 'conversation_id', 'conversation_id');
    }
    
    // ✅ ĐÚNG: Không có where
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'sender_id', 'customerID');
    }
    
    // ✅ ĐÚNG: Không có where
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'sender_id', 'employeeID');
    }
    
    // ============================================================================
    // ACCESSOR - Get sender động
    // ============================================================================
    
    public function getSenderAttribute()
    {
        if ($this->sender_type === 'customer') {
            return $this->customer;
        } elseif ($this->sender_type === 'employee') {
            return $this->employee;
        }
        return null;
    }
    
    // ============================================================================
    // SCOPES
    // ============================================================================
    
    public function scopeByConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }
    
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
    
    public function scopeFromCustomers($query)
    {
        return $query->where('sender_type', 'customer');
    }
    
    public function scopeFromEmployees($query)
    {
        return $query->where('sender_type', 'employee');
    }
}