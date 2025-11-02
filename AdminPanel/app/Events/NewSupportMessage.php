<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NewSupportMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(SupportMessage $message)
    {
        $this->message = $message;
        
        Log::info('🚀 NewSupportMessage event created', [
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_type' => $message->sender_type,
            'sender_id' => $message->sender_id
        ]);
    }

    /**
     * ✅ CRITICAL: Broadcast to BOTH admin AND customer channels
     */
    public function broadcastOn()
    {
        $channels = [];
        
        // 1. Admin channel (Private)
        $adminChannel = new PrivateChannel('admin.support.notifications');
        $channels[] = $adminChannel;
        Log::info('📡 Broadcasting to ADMIN channel: admin.support.notifications');
        
        // 2. Customer channel (Public) - CRITICAL FIX
        try {
            $conversation = DB::table('support_conversations')
                ->where('conversation_id', $this->message->conversation_id)
                ->first();
            
            if ($conversation && $conversation->customer_id) {
                $customerChannel = 'customer-support-' . $conversation->customer_id;
                
                // ✅ IMPORTANT: Must be PUBLIC channel
                $channels[] = new Channel($customerChannel);
                
                Log::info('📡 Broadcasting to CUSTOMER channel: ' . $customerChannel, [
                    'customer_id' => $conversation->customer_id,
                    'conversation_id' => $this->message->conversation_id
                ]);
            } else {
                Log::warning('⚠️ No customer found for conversation: ' . $this->message->conversation_id);
            }
        } catch (\Exception $e) {
            Log::error('❌ Error broadcasting to customer: ' . $e->getMessage());
        }
        
        Log::info('📤 Total channels: ' . count($channels));
        
        return $channels;
    }

    public function broadcastAs()
    {
        return 'support.message.sent';
    }

    public function broadcastWith()
    {
        $data = [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_type' => $this->message->sender_type,
                'sender_id' => $this->message->sender_id,
                'message' => $this->message->message,
                'attachment_path' => $this->message->attachment_path,
                'attachment_name' => $this->message->attachment_name,
                'is_read' => $this->message->is_read,
                'created_at' => $this->message->created_at->toIso8601String(),
                'sender' => $this->getSenderInfo()
            ]
        ];
        
        Log::info('📤 Broadcasting data', ['message_id' => $this->message->id]);
        
        return $data;
    }
    
    private function getSenderInfo()
    {
        if ($this->message->sender_type === 'customer') {
            if (!$this->message->relationLoaded('customer')) {
                $this->message->load('customer');
            }
            
            if ($this->message->customer) {
                return [
                    'id' => $this->message->customer->customerID,
                    'fullName' => $this->message->customer->fullName,
                    'email' => $this->message->customer->email,
                    'type' => 'customer'
                ];
            }
        } elseif ($this->message->sender_type === 'employee') {
            if (!$this->message->relationLoaded('employee')) {
                $this->message->load('employee');
            }
            
            if ($this->message->employee) {
                return [
                    'id' => $this->message->employee->employeeID,
                    'fullName' => $this->message->employee->fullName,
                    'img_url' => $this->message->employee->img_url ?? null,
                    'type' => 'employee'
                ];
            }
        }
        
        return [
            'id' => $this->message->sender_id,
            'fullName' => 'Unknown',
            'type' => $this->message->sender_type
        ];
    }
}