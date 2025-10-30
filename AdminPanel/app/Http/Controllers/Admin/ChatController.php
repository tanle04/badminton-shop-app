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
        Log::info('Bắt đầu gửi tin nhắn...', $request->all()); // <-- LOG 1

        $request->validate([
            'receiver_id' => 'required|exists:employees,employeeID',
            'message' => 'required|string|max:1000',
        ]);

        $sender = Auth::guard('admin')->user();

        $message = Message::create([
            'sender_id' => $sender->employeeID,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);

        Log::info('Tin nhắn đã lưu vào DB. Đang broadcast...', $message->toArray()); // <-- LOG 2

        // Gửi event đến WebSocket server, toOthers() để người gửi không tự nhận lại tin nhắn
        broadcast(new NewChatMessage($message))->toOthers();

        Log::info('Đã broadcast xong.'); // <-- LOG 3

        // Trả về tin nhắn đã lưu (Model Message đã tự động nạp sender)
        return response()->json(['status' => 'success', 'message' => $message]);
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
