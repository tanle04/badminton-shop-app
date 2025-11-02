<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AdminPanelService;
use App\Models\SupportMessage;
use App\Events\NewSupportMessage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SupportController extends Controller
{
    private $adminPanel;

    public function __construct(AdminPanelService $adminPanel)
    {
        $this->adminPanel = $adminPanel;
    }

    /**
     * POST /api/v1/support/init
     */
    public function initConversation(Request $request)
    {
        try {
            $customer = $request->user();
            
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $customerId = $customer->customerID;

            $result = $this->adminPanel->initConversation($customerId);

            if ($result && isset($result['success']) && $result['success']) {
                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to init conversation'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Init conversation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }

    /**
     * POST /api/v1/support/send
     * ✅ FIXED: Broadcast event khi Android gửi tin nhắn
     */
    public function sendMessage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversation_id' => 'required|string',
                'message' => 'required|string|max:2000',
                'attachment' => 'nullable|file|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = $request->user();
            $customerId = $customer->customerID;

            // Upload attachment
            $attachmentUrl = null;
            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('support-attachments', 'public');
                $attachmentUrl = url('storage/' . $path);
            }

            // ✅ LOG TRƯỚC KHI GỌI ADMIN PANEL
            Log::info('🎯 [ANDROID] Sending message via AdminPanel', [
                'conversation_id' => $request->conversation_id,
                'customer_id' => $customerId,
                'message' => substr($request->message, 0, 50)
            ]);

            // Gọi AdminPanel
            $result = $this->adminPanel->sendMessage(
                $request->conversation_id,
                $customerId,
                $request->message,
                $attachmentUrl
            );

            if ($result && isset($result['success']) && $result['success']) {
                
                // ✅ QUAN TRỌNG: Lấy message ID từ response
                if (isset($result['data']['message_id'])) {
                    $messageId = $result['data']['message_id'];
                    
                    // Load message từ database
                    $message = SupportMessage::with(['customer', 'employee'])->find($messageId);
                    
                    if ($message) {
                        // ✅ BROADCAST EVENT
                        Log::info('📤 [ANDROID] Broadcasting message', [
                            'id' => $messageId,
                            'conversation_id' => $request->conversation_id
                        ]);
                        
                        broadcast(new NewSupportMessage($message))->toOthers();
                        
                        Log::info('✅ [ANDROID] Broadcast completed');
                    } else {
                        Log::warning('⚠️ [ANDROID] Message not found for broadcast', [
                            'message_id' => $messageId
                        ]);
                    }
                }
                
                return response()->json($result, 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);

        } catch (\Exception $e) {
            Log::error('❌ [ANDROID] Send message error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/v1/support/messages
     */
    public function getMessages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'conversation_id' => 'required|string',
                'page' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $customer = $request->user();
            $customerId = $customer->customerID;

            $result = $this->adminPanel->getMessages(
                $request->conversation_id,
                $customerId,
                $request->input('page', 1)
            );

            if ($result && isset($result['success']) && $result['success']) {
                return response()->json($result);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to get messages'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Get messages error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
}