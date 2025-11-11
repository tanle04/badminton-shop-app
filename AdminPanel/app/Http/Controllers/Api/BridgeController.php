<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\SupportMessageSent;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bridge Controller - Nhận request từ Backend PHP thuần
 * để broadcast events qua Pusher
 */
class BridgeController extends Controller
{
    /**
     * Broadcast một message đã được tạo trong database
     * 
     * POST /api/bridge/support/broadcast-message
     * Header: X-API-Key: your-secret-key
     * Body: { "message_id": 92 }
     */
    public function broadcastMessage(Request $request)
    {
        try {
            $request->validate([
                'message_id' => 'required|integer'
            ]);
            
            $messageId = $request->input('message_id');
            
            Log::info('[BRIDGE] 🔔 Broadcast request received', [
                'message_id' => $messageId,
                'ip' => $request->ip()
            ]);
            
            // Lấy message từ database
            $message = DB::table('support_messages as m')
                ->leftJoin('support_conversations as c', 'm.conversation_id', '=', 'c.conversation_id')
                ->leftJoin('customers as cust', function($join) {
                    $join->on('m.sender_id', '=', 'cust.customerID')
                         ->where('m.sender_type', '=', 'customer');
                })
                ->leftJoin('employees as emp', function($join) {
                    $join->on('m.sender_id', '=', 'emp.employeeID')
                         ->where('m.sender_type', '=', 'employee');
                })
                ->where('m.id', $messageId)
                ->select(
                    'm.*',
                    'c.customer_id',
                    'cust.fullName as customer_name',
                    'emp.fullName as employee_name',
                    'emp.img_url as employee_img'
                )
                ->first();
            
            if (!$message) {
                Log::warning('[BRIDGE] ❌ Message not found', ['message_id' => $messageId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found'
                ], 404);
            }
            
            // Build sender info
            $sender = null;
            if ($message->sender_type === 'customer') {
                $sender = [
                    'fullName' => $message->customer_name ?? 'Customer',
                    'type' => 'customer'
                ];
            } elseif ($message->sender_type === 'employee') {
                $sender = [
                    'fullName' => $message->employee_name ?? 'Employee',
                    'img_url' => $message->employee_img,
                    'type' => 'employee'
                ];
            } else {
                $sender = ['fullName' => 'System', 'type' => 'system'];
            }
            
            // Prepare broadcast data
            $broadcastData = [
                'message' => [
                    'id' => (int)$message->id,
                    'conversation_id' => $message->conversation_id,
                    'sender_type' => $message->sender_type,
                    'message' => $message->message,
                    'attachment_path' => $message->attachment_path,
                    'attachment_name' => $message->attachment_name,
                    'created_at' => $message->created_at,
                    'sender' => $sender
                ]
            ];
            
            // Broadcast to customer channel
            $channelName = "customer-support-{$message->customer_id}";
            
            Log::info('[BRIDGE] 🔔 Broadcasting to Pusher', [
                'channel' => $channelName,
                'event' => 'support.message.sent',
                'message_id' => $messageId
            ]);
            
            // Trigger Pusher event
            event(new SupportMessageSent($channelName, $broadcastData));
            
            Log::info('[BRIDGE] ✅ Broadcast successful');
            
            return response()->json([
                'success' => true,
                'message' => 'Broadcast successful',
                'channel' => $channelName,
                'message_id' => $messageId
            ]);
            
        } catch (\Exception $e) {
            Log::error('[BRIDGE] ❌ Broadcast error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Broadcast failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Health check endpoint
     */
    public function health(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Bridge API is healthy',
            'timestamp' => now()->toIso8601String(),
            'pusher_configured' => config('broadcasting.default') === 'pusher'
        ]);
    }
}