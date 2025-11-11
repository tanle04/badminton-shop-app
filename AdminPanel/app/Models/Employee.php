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
        'is_active', // ⭐ THÊM DÒNG NÀY
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    public $timestamps = false; 
    
    // ... (Phần còn lại của Model giữ nguyên) ...

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
     /**
     * Get the user's full name for AdminLTE
     */
    public function adminlte_name()
    {
        return $this->fullName;
    }

    /**
     * Get the user's description (role) for AdminLTE
     */
    public function adminlte_desc()
    {
        // Chuyển role sang tiếng Việt
        $roles = [
            'Admin' => 'Quản trị viên',
            'Staff' => 'Nhân viên',
            'Marketer' => 'Marketing',
        ];

        return $roles[$this->role] ?? $this->role;
    }

    /**
     * Get the user's image URL for AdminLTE
     */
    public function adminlte_image()
    {
        if ($this->img_url) {
            return asset('storage/' . $this->img_url);
        }
        
        // Default avatar if no image
        return asset('vendor/adminlte/dist/img/user2-160x160.jpg');
    }

    /**
     * Get the user's profile URL for AdminLTE
     */
    public function adminlte_profile_url()
    {
        return false; // Không có trang profile
    }
}
