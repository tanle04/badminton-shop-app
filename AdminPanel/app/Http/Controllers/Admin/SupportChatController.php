<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMessage;
use App\Models\SupportConversation;
use App\Models\Customer;
use App\Events\NewSupportMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 

class SupportChatController extends Controller
{
    public function index()
    {
        return view('admin.support-chat.index');
    }

    public function getConversations(Request $request)
{
    $filter = $request->input('filter', 'all');
    $currentEmployeeId = Auth::guard('admin')->id();
    
    Log::info('📋 [SUPPORT] Get conversations', [
        'employee_id' => $currentEmployeeId,
        'filter' => $filter
    ]);
    
    $query = DB::table('support_conversations as sc')
        ->leftJoin('customers as c', 'sc.customer_id', '=', 'c.customerID')
        ->leftJoin('employees as e', 'sc.assigned_employee_id', '=', 'e.employeeID')
        ->leftJoin(DB::raw('(
            SELECT conversation_id, MAX(created_at) as latest_at
            FROM support_messages
            GROUP BY conversation_id
        ) as lm'), 'sc.conversation_id', '=', 'lm.conversation_id')
        ->leftJoin('support_messages as latest_msg', function($join) {
            $join->on('sc.conversation_id', '=', 'latest_msg.conversation_id')
                 ->on('lm.latest_at', '=', 'latest_msg.created_at');
        })
        ->select(
            'sc.conversation_id',
            'sc.customer_id',
            'sc.status',
            'sc.assigned_employee_id',
            'sc.last_message_at',
            'c.fullName as customer_name',
            'c.email as customer_email',
            'c.phone as customer_phone',
            'e.fullName as employee_name',
            'latest_msg.message as last_message',
            'latest_msg.sender_type as last_sender_type',
            'latest_msg.created_at as last_message_time',
            DB::raw('(SELECT COUNT(*) FROM support_messages sm 
                     WHERE sm.conversation_id = sc.conversation_id 
                     AND sm.sender_type = "customer" 
                     AND sm.is_read = 0) as unread_count')
        );
    
    // ✅ CRITICAL: CHỈ LẤY CONVERSATION CỦA NHÂN VIÊN HIỆN TẠI
    switch ($filter) {
        case 'assigned':
            // Chỉ conversation được assign cho mình
            $query->where('sc.assigned_employee_id', $currentEmployeeId)
                  ->where('sc.status', 'open');
            break;
            
        case 'unassigned':
            // Conversation chưa assign (tất cả nhân viên đều thấy)
            $query->whereNull('sc.assigned_employee_id')
                  ->where('sc.status', 'open');
            break;
            
        case 'open':
            // Conversation open của mình HOẶC chưa assign
            $query->where('sc.status', 'open')
                  ->where(function($q) use ($currentEmployeeId) {
                      $q->where('sc.assigned_employee_id', $currentEmployeeId)
                        ->orWhereNull('sc.assigned_employee_id');
                  });
            break;
            
        default: // 'all'
            // ✅ CHỈ LẤY CONVERSATION CỦA MÌNH + CHƯA ASSIGN
            $query->where(function($q) use ($currentEmployeeId) {
                $q->where('sc.assigned_employee_id', $currentEmployeeId)
                  ->orWhereNull('sc.assigned_employee_id');
            });
            break;
    }
    
    $conversations = $query->orderBy('sc.last_message_at', 'desc')
                           ->get();
    
    $result = $conversations->map(function($conv) {
        return [
            'conversation_id' => $conv->conversation_id,
            'status' => $conv->status,
            'customer' => [
                'customerID' => $conv->customer_id,
                'fullName' => $conv->customer_name,
                'email' => $conv->customer_email,
                'phone' => $conv->customer_phone,
            ],
            'assigned_employee' => $conv->assigned_employee_id ? [
                'employeeID' => $conv->assigned_employee_id,
                'fullName' => $conv->employee_name,
            ] : null,
            'latest_message' => $conv->last_message ? [
                'message' => $conv->last_message,
                'sender_type' => $conv->last_sender_type,
                'created_at' => $conv->last_message_time,
            ] : null,
            'unread_count' => (int)$conv->unread_count,
            'last_message_at' => $conv->last_message_at,
        ];
    });
    
    Log::info('✅ [SUPPORT] Returning ' . $result->count() . ' conversations');
    
    return response()->json($result);
}

    public function getConversationHistory($conversationId)
    {
        try {
            $conversation = DB::table('support_conversations as sc')
                ->leftJoin('customers as c', 'sc.customer_id', '=', 'c.customerID')
                ->leftJoin('employees as e', 'sc.assigned_employee_id', '=', 'e.employeeID')
                ->where('sc.conversation_id', $conversationId)
                ->select(
                    'sc.*',
                    'c.customerID',
                    'c.fullName as customer_name',
                    'c.email as customer_email',
                    'c.phone as customer_phone',
                    'e.employeeID as emp_id',
                    'e.fullName as emp_name'
                )
                ->first();

            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            $messages = DB::table('support_messages as sm')
                ->leftJoin('customers as c', function($join) {
                    $join->on('sm.sender_id', '=', 'c.customerID')
                         ->where('sm.sender_type', '=', 'customer');
                })
                ->leftJoin('employees as e', function($join) {
                    $join->on('sm.sender_id', '=', 'e.employeeID')
                         ->where('sm.sender_type', '=', 'employee');
                })
                ->where('sm.conversation_id', $conversationId)
                ->orderBy('sm.created_at', 'asc')
                ->select(
                    'sm.*',
                    'c.customerID as c_id',
                    'c.fullName as c_name',
                    'c.email as c_email',
                    'e.employeeID as e_id',
                    'e.fullName as e_name',
                    'e.img_url as e_img'
                )
                ->get()
                ->map(function ($msg) {
                    $senderInfo = null;

                    if ($msg->sender_type === 'customer' && $msg->c_id) {
                        $senderInfo = [
                            'id' => $msg->c_id,
                            'fullName' => $msg->c_name,
                            'email' => $msg->c_email,
                        ];
                    } elseif ($msg->sender_type === 'employee' && $msg->e_id) {
                        $senderInfo = [
                            'id' => $msg->e_id,
                            'fullName' => $msg->e_name,
                            'img_url' => $msg->e_img,
                        ];
                    }

                    return [
                        'id' => $msg->id,
                        'conversation_id' => $msg->conversation_id,
                        'sender_type' => $msg->sender_type,
                        'sender_id' => $msg->sender_id,
                        'message' => $msg->message,
                        'attachment_path' => $msg->attachment_path,
                        'attachment_name' => $msg->attachment_name,
                        'is_read' => (bool) $msg->is_read,
                        'created_at' => $msg->created_at,
                        'sender' => $senderInfo,
                    ];
                });

            return response()->json([
                'conversation' => [
                    'conversation_id' => $conversation->conversation_id,
                    'customer' => [
                        'customerID' => $conversation->customerID,
                        'fullName' => $conversation->customer_name,
                        'email' => $conversation->customer_email,
                        'phone' => $conversation->customer_phone ?? 'N/A',
                    ],
                    'assigned_employee' => $conversation->emp_id ? [
                        'employeeID' => $conversation->emp_id,
                        'fullName' => $conversation->emp_name,
                    ] : null,
                    'status' => $conversation->status,
                    'priority' => $conversation->priority,
                    'subject' => $conversation->subject,
                ],
                'messages' => $messages,
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ Get history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ FIXED: Broadcast event khi gửi tin nhắn
     */
    public function sendMessage(Request $request)
    {
        try {
            $validated = $request->validate([
                'conversation_id' => 'required',
                'message' => 'required|string|max:2000',
                'attachment' => 'nullable|file|max:10240',
            ]);

            $employeeId = Auth::guard('admin')->id();

            $conversation = DB::table('support_conversations')
                ->where('conversation_id', $validated['conversation_id'])
                ->first();

            if (!$conversation) {
                return response()->json(['error' => 'Conversation not found'], 404);
            }

            $attachmentPath = null;
            $attachmentName = null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $attachmentName = $file->getClientOriginalName();
                $attachmentPath = $file->store('support-attachments', 'public');
            }

            // Insert message
            $messageId = DB::table('support_messages')->insertGetId([
                'conversation_id' => $validated['conversation_id'],
                'sender_type' => 'employee',
                'sender_id' => $employeeId,
                'message' => $validated['message'],
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ✅ QUAN TRỌNG: Load message để broadcast
            $message = SupportMessage::with(['employee', 'customer'])->find($messageId);

            // Update conversation
            DB::table('support_conversations')
                ->where('conversation_id', $validated['conversation_id'])
                ->update(['last_message_at' => now()]);

            // Auto-assign
            if (!$conversation->assigned_employee_id) {
                DB::table('support_conversations')
                    ->where('conversation_id', $validated['conversation_id'])
                    ->update(['assigned_employee_id' => $employeeId]);
            }

            // ✅ BROADCAST EVENT
            \Log::info('📤 [ADMIN] Broadcasting message', [
                'id' => $messageId,
                'conversation_id' => $validated['conversation_id']
            ]);
            
            broadcast(new NewSupportMessage($message))->toOthers();
            
            \Log::info('✅ [ADMIN] Broadcast completed');

            $employee = DB::table('employees')
                ->where('employeeID', $employeeId)
                ->first();

            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $messageId,
                    'conversation_id' => $validated['conversation_id'],
                    'sender_type' => 'employee',
                    'sender_id' => $employeeId,
                    'message' => $validated['message'],
                    'attachment_path' => $attachmentPath,
                    'attachment_name' => $attachmentName,
                    'created_at' => now()->toIso8601String(),
                    'sender' => [
                        'id' => $employee->employeeID,
                        'fullName' => $employee->fullName,
                        'img_url' => $employee->img_url ?? null,
                        'type' => 'employee'
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('❌ [ADMIN] Send message error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignConversation(Request $request, $conversationId)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,employeeID'
            ]);

            DB::table('support_conversations')
                ->where('conversation_id', $conversationId)
                ->update([
                    'assigned_employee_id' => $validated['employee_id'],
                    'status' => 'open'
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã assign cuộc hội thoại'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function closeConversation($conversationId)
    {
        try {
            DB::table('support_conversations')
                ->where('conversation_id', $conversationId)
                ->update(['status' => 'closed']);

            return response()->json([
                'success' => true,
                'message' => 'Đã đóng cuộc hội thoại'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function markAsRead($conversationId)
    {
        try {
            DB::table('support_messages')
                ->where('conversation_id', $conversationId)
                ->where('sender_type', 'customer')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStats()
    {
        try {
            $employeeId = Auth::guard('admin')->id();

            $stats = [
                'total_open' => DB::table('support_conversations')
                    ->where('status', 'open')
                    ->count(),
                'assigned_to_me' => DB::table('support_conversations')
                    ->where('assigned_employee_id', $employeeId)
                    ->count(),
                'unassigned' => DB::table('support_conversations')
                    ->whereNull('assigned_employee_id')
                    ->count(),
                'total_unread' => DB::table('support_messages')
                    ->where('sender_type', 'customer')
                    ->where('is_read', false)
                    ->count(),
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            \Log::error('❌ Get stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}