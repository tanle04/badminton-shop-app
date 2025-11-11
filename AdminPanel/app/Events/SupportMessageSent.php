<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Event để broadcast tin nhắn support qua Pusher
 * Được trigger từ BridgeController
 */
class SupportMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $channelName;
    public $broadcastData;

    /**
     * Create a new event instance.
     *
     * @param string $channelName - Tên channel (ví dụ: "customer-support-1")
     * @param array $broadcastData - Data để broadcast
     */
    public function __construct(string $channelName, array $broadcastData)
    {
        $this->channelName = $channelName;
        $this->broadcastData = $broadcastData;
        
        Log::info('🚀 SupportMessageSent event created', [
            'channel' => $channelName,
            'message_id' => $broadcastData['message']['id'] ?? null
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     * 
     * ✅ Sử dụng PUBLIC Channel vì mobile app không authenticate
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // ✅ PUBLIC Channel - không cần authentication
        $channel = new Channel($this->channelName);
        
        Log::info('📡 Broadcasting to PUBLIC channel: ' . $this->channelName);
        
        return $channel;
    }

    /**
     * Event name that will be broadcast
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'support.message.sent';
    }

    /**
     * Data to broadcast
     *
     * @return array
     */
    public function broadcastWith()
    {
        Log::info('📤 Broadcasting data', [
            'channel' => $this->channelName,
            'message_id' => $this->broadcastData['message']['id'] ?? null
        ]);
        
        return $this->broadcastData;
    }
}