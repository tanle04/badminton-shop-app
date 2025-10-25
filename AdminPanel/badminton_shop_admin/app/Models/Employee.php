<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use HasFactory, Notifiable;

    // Tên bảng trong CSDL
    protected $table = 'employees';

    // Khóa chính trong CSDL là employeeID
    protected $primaryKey = 'employeeID'; 

    // Disable timestamps vì bảng 'employees' không có created_at và updated_at
    public $timestamps = false; 

    protected $fillable = [
        // Sử dụng 'fullName' để khớp với CSDL
        'fullName', 
        'email',
        'password',
        'role', // 'admin', 'staff', 'marketing'
        'img_url', // Thêm vào fillable nếu Controller có thể tạo hoặc sửa url ảnh
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        // ĐÃ LOẠI BỎ 'password' => 'hashed' để tránh lỗi InvalidCastException
        'email_verified_at' => 'datetime', 
    ];

    /**
     * Override getter để Controller có thể truy cập $employee->name
     * Mặc dù cột CSDL là 'fullName'
     */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['fullName'] ?? null;
    }
    
    /**
     * Override setter để Controller có thể gán $employee->name = '...'
     * và lưu vào cột 'fullName'
     */
    public function setNameAttribute($value)
    {
        $this->attributes['fullName'] = $value;
    }

    /**
     * Kiểm tra quyền hạn của nhân viên.
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // Thiết lập mối quan hệ: Nhân viên quản lý các Sliders
    public function sliders()
    {
        // Quan hệ hasMany: (Model liên quan, khóa ngoại trong bảng Sliders, khóa cục bộ trong bảng Employees)
        return $this->hasMany(Slider::class, 'employeeID', 'employeeID');
    }
}
