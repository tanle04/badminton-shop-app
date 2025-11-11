<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CustomerSupportMessage extends Model
{
    use HasFactory;
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
        'assigned_employee_id',
        'status',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public static function generateConversationId(int $customerId): string
    {
        return 'CONV-' . $customerId . '-' . Str::random(8);
    }

    public function customer(): BelongsTo
    {
        // ĐÃ SỬA LỖI: Bỏ điều kiện where() để tránh áp dụng cho bảng customers
        return $this->belongsTo(Customer::class, 'sender_id', 'customerID');
    }

    public function employee(): BelongsTo
    {
        // ĐÃ SỬA LỖI: Bỏ điều kiện where() để tránh áp dụng cho bảng employees
        return $this->belongsTo(Employee::class, 'sender_id', 'employeeID');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id', 'employeeID');
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByConversation($query, string $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }
}