<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // Khi event được tạo, Laravel sẽ tự động gọi $message->load('sender') 
        // vì bạn đã định nghĩa protected $with trong Model Message
        $this->message = $message;
    }

    /**
     * Định nghĩa kênh mà tin nhắn sẽ được broadcast (gửi) đến.
     */
    public function broadcastOn(): PrivateChannel
    {
        // Gửi tin nhắn đến kênh riêng tư của người nhận (ID của người nhận)
        // Kênh này chỉ có thể được lắng nghe bởi người dùng có ID tương ứng.
        return new PrivateChannel('employee.chat.' . $this->message->receiver_id);
    }

    /**
     * Định nghĩa tên event mà Front-end (JavaScript) sẽ lắng nghe.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}