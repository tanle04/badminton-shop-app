<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportConversation extends Model
{
    protected $table = 'support_conversations';
    protected $primaryKey = 'conversation_id';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'conversation_id',
        'customer_id',
        'assigned_employee_id',
        'status',
        'priority',
        'subject',
        'last_message_at',
    ];
    
    protected $casts = [
        'last_message_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================
    
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customerID');
    }
    
    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id', 'employeeID');
    }
    
    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'conversation_id', 'conversation_id');
    }
    
    public function latestMessage()
    {
        return $this->hasOne(SupportMessage::class, 'conversation_id', 'conversation_id')
            ->latest('created_at');
    }
    
    // ============================================================================
    // SCOPES
    // ============================================================================
    
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
    
    public function scopeAssignedTo($query, $employeeId)
    {
        return $query->where('assigned_employee_id', $employeeId);
    }
    
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_employee_id');
    }
    
    // ============================================================================
    // METHODS
    // ============================================================================
    
    public function unreadMessagesCount()
    {
        return $this->messages()
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->count();
    }
    
    public function assignTo($employeeId)
    {
        $this->update([
            'assigned_employee_id' => $employeeId,
            'status' => 'open'
        ]);
    }
    
    public function close()
    {
        $this->update(['status' => 'closed']);
    }
    
    public function reopen()
    {
        $this->update(['status' => 'open']);
    }
    
    public function updateLastMessageTime()
    {
        $this->update(['last_message_at' => now()]);
    }
}