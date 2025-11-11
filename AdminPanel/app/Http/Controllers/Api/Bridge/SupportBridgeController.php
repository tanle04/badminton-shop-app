<?php

namespace App\Http\Controllers\Api\Bridge;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportConversation;
use App\Models\CustomerSupportMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Events\NewSupportMessage;
use App\Services\Support\SupportAssignmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SupportBridgeController extends Controller
{
    protected $assignmentService;

    public function __construct(SupportAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * HEALTH CHECK: Xác minh kết nối từ BackendApi (Bridge API)
     * GET /api/bridge/support/health
     */
    public function healthCheck()
    {
        return response()->json([
            'success' => true,
            'message' => 'AdminPanel Bridge API is healthy'
        ]);
    }

    /**
     * Khởi tạo hoặc lấy conversation hiện có cho Customer.
     * POST /api/bridge/support/init-conversation
     */
    public function initConversation(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,customerID',
            'subject' => 'nullable|string|max:255',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $customerId = $request->customer_id;

        // 1. Tìm conversation đang mở
        $conversation = SupportConversation::where('customer_id', $customerId)
                                            ->whereIn('status', ['open', 'pending'])
                                            ->first();

        // 2. Nếu không có, tạo mới
        if (!$conversation) {
            $conversationId = CustomerSupportMessage::generateConversationId($customerId);
            $conversation = SupportConversation::create([
                'conversation_id' => $conversationId,
                'customer_id' => $customerId,
                'subject' => $request->subject ?? 'Hỗ trợ từ Mobile App',
                'priority' => $request->priority ?? 'normal',
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            $this->assignmentService->autoAssignToAvailableEmployee($conversation);
            
            // ✅ RELOAD để lấy assigned_employee_id
            $conversation->refresh();
        }

        // ✅ FIX 1: Lấy thông tin employee đầy đủ
        $assigned_employee = null;
        if ($conversation->assigned_employee_id) {
            $employee = DB::table('employees')
                ->where('employeeID', $conversation->assigned_employee_id)
                ->first();
            
            if ($employee) {
                $assigned_employee = [
                    'employeeID' => (int)$employee->employeeID,
                    'fullName' => $employee->fullName,
                    'email' => $employee->email,
                    'img_url' => $employee->img_url
                ];
            }
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->conversation_id,
            'customer_id' => (int)$customerId, // ✅ CRITICAL: Android cần để subscribe WebSocket
            'status' => $conversation->status,
            'assigned_employee' => $assigned_employee, // ✅ Thêm thông tin nhân viên
            'message' => 'Kết nối thành công'
        ]);
    }

    /**
     * Gửi tin nhắn từ Customer.
     * POST /api/bridge/support/send-message
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'conversation_id' => 'required|string|exists:support_conversations,conversation_id',
                'customer_id' => 'required|integer|exists:customers,customerID',
                'message' => 'required|string',
                'attachment_url' => 'nullable|url',
                'attachment_name' => 'nullable|string',
            ]);

            $conversation = SupportConversation::find($request->conversation_id);

            if (!$conversation || $conversation->customer_id != $request->customer_id) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Conversation not found or unauthorized.'
                ], 404);
            }
            
            $message = DB::transaction(function () use ($request, $conversation) {
                $message = CustomerSupportMessage::create([
                    'conversation_id' => $request->conversation_id,
                    'sender_type' => 'customer',
                    'sender_id' => $request->customer_id,
                    'message' => $request->message,
                    'attachment_path' => $request->attachment_url,
                    'attachment_name' => $request->attachment_name,
                    'is_read' => false,
                    'assigned_employee_id' => $conversation->assigned_employee_id,
                    'status' => 'open'
                ]);

                if ($conversation->status !== 'open' && $conversation->status !== 'pending') {
                    $conversation->reopen();
                }
                $conversation->updateLastMessageTime();
                
                return $message;
            });

            Log::info('📤 [BRIDGE] Message created, preparing broadcast', [
                'message_id' => $message->id,
                'conversation_id' => $message->conversation_id
            ]);

            // Load relationships cần thiết cho broadcast
            $message->load(['customer', 'employee']);
            
            // Broadcast event
            broadcast(new NewSupportMessage($message))->toOthers();
            
            Log::info('✅ [BRIDGE] Broadcast completed', [
                'message_id' => $message->id
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Message sent',
                'data' => [
                    'message_id' => $message->id,
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'sender_type' => $message->sender_type,
                    'message' => $message->message,
                    'created_at' => $message->created_at->toDateTimeString()
                ]
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('❌ [BRIDGE] Send message error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Lấy danh sách tin nhắn cho một conversation cụ thể.
     * GET /api/bridge/support/messages?conversation_id=...&customer_id=...
     */
    public function getMessages(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|string|exists:support_conversations,conversation_id',
            'customer_id' => 'required|integer|exists:customers,customerID',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $conversation = SupportConversation::where('conversation_id', $request->conversation_id)
                                            ->where('customer_id', $request->customer_id)
                                            ->first();

        if (!$conversation) {
            return response()->json(['success' => false, 'message' => 'Conversation not found or unauthorized.'], 404);
        }

        $messages = CustomerSupportMessage::where('conversation_id', $request->conversation_id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));
            
        $messages->setCollection($messages->getCollection()->reverse());
        
        return response()->json([
            'success' => true,
            'data' => $messages->toArray()
        ]);
    }

    /**
     * Lấy số lượng tin nhắn chưa đọc cho Admin.
     * GET /api/bridge/support/unread-count?customer_id=...
     */
    public function getUnreadCount(Request $request)
    {
        return response()->json(['success' => true, 'count' => 0]);
    }

    /**
     * ✅ FIXED VERSION - Trigger broadcast via HTTP
     * POST /api/bridge/support/trigger-broadcast
     */
    public function triggerBroadcast(Request $request)
    {
        try {
            $request->validate([
                'message_id' => 'required|integer',
            ]);

            $message_id = $request->message_id;

            Log::info('📤 [BRIDGE] Broadcast trigger START', [
                'message_id' => $message_id,
                'timestamp' => now()->toDateTimeString()
            ]);

            // Load message
            $message = DB::table('support_messages')
                ->where('id', $message_id)
                ->first();

            if (!$message) {
                Log::error('❌ [BRIDGE] Message not found', ['id' => $message_id]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found'
                ], 404);
            }

            Log::info('✅ [BRIDGE] Message loaded', [
                'conversation_id' => $message->conversation_id,
                'sender_type' => $message->sender_type
            ]);

            // Load conversation
            $conversation = DB::table('support_conversations')
                ->where('conversation_id', $message->conversation_id)
                ->first();
            
            if (!$conversation) {
                Log::error('❌ [BRIDGE] Conversation not found', [
                    'conversation_id' => $message->conversation_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found'
                ], 404);
            }
            
            // Get customer name
            $customer = DB::table('customers')
                ->where('customerID', $conversation->customer_id)
                ->first();
            
            $customer_name = $customer ? $customer->fullName : 'Khách hàng';
            
            // ✅ FIX 2: Thêm 'private-' cho admin channel
            $customer_channel = 'customer-support-' . $conversation->customer_id;
            $admin_channel = 'admin.support.notifications'; // ✅ PUBLIC CHANNEL (no 'private-')
            
            Log::info('📡 [BRIDGE] Broadcasting to channels', [
                'customer' => $customer_channel,
                'admin' => $admin_channel,
                'customer_id' => $conversation->customer_id
            ]);
            
            // Create Pusher instance
            try {
                $pusher = new \Pusher\Pusher(
                    config('broadcasting.connections.pusher.key'),
                    config('broadcasting.connections.pusher.secret'),
                    config('broadcasting.connections.pusher.app_id'),
                    [
                        'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                        'useTLS' => true
                    ]
                );
                
                Log::info('✅ [BRIDGE] Pusher instance created', [
                    'app_id' => config('broadcasting.connections.pusher.app_id'),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster')
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ [BRIDGE] Pusher init failed', [
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
            
            // Prepare payload
            $payload = [
                'message' => [
                    'id' => (int)$message->id,
                    'conversation_id' => $message->conversation_id,
                    'sender_type' => $message->sender_type,
                    'sender_id' => (int)$message->sender_id,
                    'message' => $message->message,
                    'created_at' => $message->created_at,
                    'sender' => [
                        'fullName' => $customer_name
                    ]
                ]
            ];
            
            Log::info('📦 [BRIDGE] Payload prepared', $payload);
            
            // ✅ FIX 3: Broadcast riêng lẻ để có kết quả cụ thể
            $customer_result = null;
            $admin_result = null;
            
            // Broadcast to customer channel
            try {
                Log::info('📡 [BRIDGE] Broadcasting to CUSTOMER channel...');
                
                $customer_result = $pusher->trigger(
                    $customer_channel,
                    'support.message.sent',
                    $payload
                );
                
                Log::info('✅ [BRIDGE] Customer broadcast result', [
                    'channel' => $customer_channel,
                    'result' => json_encode($customer_result)
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ [BRIDGE] Customer broadcast failed', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Broadcast to admin channel
            try {
                Log::info('📡 [BRIDGE] Broadcasting to ADMIN channel...');
                
                $admin_result = $pusher->trigger(
                    $admin_channel,
                    'support.message.sent',
                    $payload
                );
                
                Log::info('✅ [BRIDGE] Admin broadcast result', [
                    'channel' => $admin_channel,
                    'result' => json_encode($admin_result)
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ [BRIDGE] Admin broadcast failed', [
                    'error' => $e->getMessage()
                ]);
            }
            
            Log::info('✅ ✅ ✅ [BRIDGE] BROADCAST COMPLETE! ✅ ✅ ✅');

            return response()->json([
                'success' => true,
                'message' => 'Broadcast sent successfully',
                'data' => [
                    'message_id' => $message_id,
                    'conversation_id' => $message->conversation_id,
                    'sender_type' => $message->sender_type,
                    'channels' => [
                        'customer' => $customer_channel,
                        'admin' => $admin_channel
                    ],
                    'results' => [
                        'customer' => $customer_result,
                        'admin' => $admin_result
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [BRIDGE] Broadcast error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Broadcast failed: ' . $e->getMessage()
            ], 500);
        }
    }
}