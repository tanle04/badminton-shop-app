<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'message'];
    
    // Tự động nạp thông tin người gửi/nhận khi lấy message
    protected $with = ['sender', 'receiver']; 
    
    /**
     * Mối quan hệ với người gửi (Employee)
     */
    public function sender(): BelongsTo
    {
        // Khóa ngoại: sender_id -> employees.employeeID
        return $this->belongsTo(Employee::class, 'sender_id', 'employeeID');
    }
    
    /**
     * Mối quan hệ với người nhận (Employee)
     */
    public function receiver(): BelongsTo
    {
        // Khóa ngoại: receiver_id -> employees.employeeID
        return $this->belongsTo(Employee::class, 'receiver_id', 'employeeID');
    }
}