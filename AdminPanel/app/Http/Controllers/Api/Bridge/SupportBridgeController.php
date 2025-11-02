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

            // DÒNG SỬA LỖI: Gọi đúng tên hàm trong Service
            $this->assignmentService->autoAssignToAvailableEmployee($conversation);
        }

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->conversation_id,
            'status' => $conversation->status
        ]);
    }

    /**
     * Gửi tin nhắn từ Customer.
     * POST /api/bridge/support/send-message
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|string|exists:support_conversations,conversation_id',
            'customer_id' => 'required|integer|exists:customers,customerID',
            'message' => 'required|string',
            'attachment_url' => 'nullable|url',
            'attachment_name' => 'nullable|string',
        ]);

        $conversation = SupportConversation::find($request->conversation_id);

        if (!$conversation || $conversation->customer_id != $request->customer_id) {
             return response()->json(['success' => false, 'message' => 'Conversation not found or unauthorized.'], 404);
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
                'assigned_employee_id' => $conversation->assigned_employee_id, // Giữ nguyên assign
                'status' => 'open' // Giả sử mở lại nếu đã đóng
            ]);

            // Cập nhật trạng thái conversation nếu cần
            if ($conversation->status !== 'open' && $conversation->status !== 'pending') {
                 $conversation->reopen();
            }
            $conversation->updateLastMessageTime();
            
            return $message;
        });

        // Broadcast tin nhắn mới cho cả customer và admin
        broadcast(new NewSupportMessage($message, 'customer', $conversation->assigned_employee_id))->toOthers();
        
        return response()->json([
            'success' => true, 
            'message' => 'Message sent',
            'data' => [
                 'id' => $message->id,
                 'conversation_id' => $message->conversation_id,
                 'sender_type' => $message->sender_type,
                 'message' => $message->message,
                 'created_at' => $message->created_at->toDateTimeString()
            ]
        ], 201);
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
            ->orderBy('created_at', 'desc') // Lấy tin nhắn mới nhất trước
            ->paginate($request->input('per_page', 50));
            
        // Đảo ngược thứ tự để Mobile App hiển thị từ cũ đến mới (tùy thuộc vào thiết kế mobile)
        $messages->setCollection($messages->getCollection()->reverse());
        
        return response()->json([
            'success' => true,
            'data' => $messages->toArray()
        ]);
    }

    /**
     * Lấy số lượng tin nhắn chưa đọc cho Admin (dành cho BackendApi dùng như một thống kê).
     * GET /api/bridge/support/unread-count?customer_id=...
     */
    public function getUnreadCount(Request $request)
    {
        // ... (Bạn có thể thêm logic này sau nếu cần)
        return response()->json(['success' => true, 'count' => 0]);
    }
}