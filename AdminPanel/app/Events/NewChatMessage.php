<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load('sender');
    }

    /**
     * ⭐ FIX: Gửi tin nhắn đến CẢ 2 NGƯỜI (sender và receiver)
     * Trả về ARRAY of channels thay vì 1 channel
     */
    public function broadcastOn(): array
    {
        return [
            // Gửi đến người NHẬN
            new PrivateChannel('employee.chat.' . $this->message->receiver_id),

            // Gửi đến người GỬI (để họ thấy tin nhắn của chính mình)
            new PrivateChannel('employee.chat.' . $this->message->sender_id),
        ];
    }

    /**
     * Tên event mà JavaScript sẽ lắng nghe
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
    

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'receiver_id' => $this->message->receiver_id,
                'message' => $this->message->message,
                'created_at' => $this->message->created_at->toISOString(),
                'sender' => [
                    'employeeID' => $this->message->sender->employeeID,
                    'fullName' => $this->message->sender->fullName,
                ]
            ]
        ];
    }
}