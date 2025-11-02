<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SupportMessage;
use App\Events\NewSupportMessage;
use Illuminate\Support\Facades\Log;

class BroadcastSupportMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:broadcast {message_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Broadcast a support message to WebSocket';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $message_id = $this->argument('message_id');
        
        Log::info('🔔 [ARTISAN] Broadcasting message', ['message_id' => $message_id]);
        $this->info("🔔 Broadcasting message ID: {$message_id}");
        
        try {
            // Get message with relations
            $message = SupportMessage::with(['customer', 'employee'])->find($message_id);
            
            if (!$message) {
                Log::error('❌ [ARTISAN] Message not found', ['message_id' => $message_id]);
                $this->error("❌ Message not found: {$message_id}");
                return 1;
            }
            
            Log::info('✅ [ARTISAN] Message loaded', [
                'conversation_id' => $message->conversation_id,
                'sender_type' => $message->sender_type,
                'sender_id' => $message->sender_id
            ]);
            
            $this->info("✅ Message loaded:");
            $this->info("   - Conversation: {$message->conversation_id}");
            $this->info("   - Sender: {$message->sender_type} (ID: {$message->sender_id})");
            $this->info("   - Content: {$message->message}");
            
            // Broadcast event
            broadcast(new NewSupportMessage($message));
            
            Log::info('✅ [ARTISAN] Broadcast completed', ['message_id' => $message_id]);
            $this->info("✅ Broadcast completed!");
            
            return 0;
            
        } catch (\Exception $e) {
            Log::error('❌ [ARTISAN] Broadcast error', [
                'message_id' => $message_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->error("❌ Error: {$e->getMessage()}");
            return 1;
        }
    }
}