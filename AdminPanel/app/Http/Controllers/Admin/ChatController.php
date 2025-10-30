<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Employee;
use App\Events\NewChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Hiển thị giao diện chat chính.
     */
    public function index()
    {
        // Trả về view chat, nơi logic JS sẽ tải danh sách nhân viên và kết nối WebSocket.
        return view('admin.chat.index');
    }

    /**
     * Lấy danh sách nhân viên (trừ chính mình) để bắt đầu chat.
     */
    public function getEmployees()
    {
        $currentEmployeeId = Auth::guard('admin')->id();
        // Lấy danh sách Employee trừ chính mình
        $employees = Employee::where('employeeID', '!=', $currentEmployeeId)
            ->select('employeeID', 'fullName', 'role', 'img_url')
            ->get();
        return response()->json($employees);
    }

    /**
     * Gửi và lưu tin nhắn vào database, sau đó broadcast qua Websocket.
     */
    public function sendMessage(Request $request)
{
    // Debug log
    \Log::info('📥 Received message request', [
        'receiver_id' => $request->receiver_id,
        'message' => $request->message,
        'sender_id' => auth()->guard('admin')->id()
    ]);
    
    try {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:employees,employeeID',
            'message' => 'required|string|max:1000'
        ]);

        $senderId = auth()->guard('admin')->id();
        
        \Log::info('✅ Validation passed', [
            'sender_id' => $senderId,
            'receiver_id' => $validated['receiver_id']
        ]);
        
        // Tạo message
        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message']
        ]);

        \Log::info('✅ Message created', ['message_id' => $message->id]);

        // Load relationships
        $message->load('sender', 'receiver');

        // Broadcast CHỈ cho receiver
        broadcast(new NewChatMessage($message))->toOthers();
        
        \Log::info('✅ Message broadcasted');

        // ✅ QUAN TRỌNG: Trả về JSON rõ ràng
        $response = [
            'success' => true,
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'message' => $message->message,
                'created_at' => $message->created_at->toISOString(),
                'sender' => [
                    'employeeID' => $message->sender->employeeID,
                    'fullName' => $message->sender->fullName,
                    'img_url' => $message->sender->img_url,
                ]
            ]
        ];
        
        \Log::info('✅ Returning response', $response);

        return response()->json($response, 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('❌ Validation error', ['errors' => $e->errors()]);
        
        return response()->json([
            'success' => false,
            'error' => 'Dữ liệu không hợp lệ',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        \Log::error('❌ Send message error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Lấy lịch sử chat giữa người gửi và người nhận.
     */
    public function getHistory($receiverId)
    {
        $senderId = Auth::guard('admin')->id();

        // Lấy tin nhắn giữa hai người (gửi-nhận hoặc nhận-gửi)
        $messages = Message::where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)
                ->where('receiver_id', $senderId);
        })
            // Giả định Model Message đã dùng protected $with = ['sender', 'receiver'];
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }
}