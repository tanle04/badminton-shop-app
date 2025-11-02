<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Employee extends Authenticatable
{
    protected $table = 'employees';
    protected $primaryKey = 'employeeID';
    
    protected $guard = 'admin';
    
    protected $fillable = [
        'fullName',
        'email',
        'password',
        'img_url',
        'role',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    public $timestamps = false; // Nếu bảng không có created_at, updated_at
    
    // ============================================================================
    // RELATIONSHIPS
    // ============================================================================
    
    public function assignedConversations()
    {
        return $this->hasMany(SupportConversation::class, 'assigned_employee_id', 'employeeID');
    }
    
    public function supportMessages()
    {
        return $this->hasMany(SupportMessage::class, 'sender_id', 'employeeID')
            ->where('sender_type', 'employee');
    }
    
    // ============================================================================
    // METHODS
    // ============================================================================
    
    public function activeConversations()
    {
        return $this->assignedConversations()->where('status', 'open');
    }
}
