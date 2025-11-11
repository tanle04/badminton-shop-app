let currentConversationId = null;
let pusherInstance = null;
let adminChannel = null;

// =========================================================================
// ✅ PUSHER INITIALIZATION - THÊM MỚI
// =========================================================================
function initializePusher() {
    console.log('🔌 [ADMIN] Initializing Pusher...');
    
    // Get Pusher config from meta tags or window object
    const pusherKey = document.querySelector('meta[name="pusher-key"]')?.content || 'c3ca7c07e100fdf6218b';
    const pusherCluster = document.querySelector('meta[name="pusher-cluster"]')?.content || 'ap1';
    
    console.log('⚙️ [ADMIN] Pusher Config:', {
        key: pusherKey,
        cluster: pusherCluster
    });
    
    // Initialize Pusher
    pusherInstance = new Pusher(pusherKey, {
        cluster: pusherCluster,
        encrypted: true,
        forceTLS: true
    });
    
    console.log('✅ [ADMIN] Pusher instance created');
    
    // ✅ SUBSCRIBE TO ADMIN CHANNEL (PUBLIC CHANNEL)
    console.log('📡 [ADMIN] Subscribing to admin.support.notifications...');
    
    adminChannel = pusherInstance.subscribe('admin.support.notifications');
    
    adminChannel.bind('pusher:subscription_succeeded', () => {
        console.log('✅ ✅ ✅ [ADMIN] Successfully subscribed to admin channel! ✅ ✅ ✅');
    });
    
    adminChannel.bind('pusher:subscription_error', (error) => {
        console.error('❌ [ADMIN] Subscription error:', error);
    });
    
    // ✅ LISTEN FOR NEW MESSAGES
    adminChannel.bind('support.message.sent', (data) => {
        console.log('📩 [ADMIN] === NEW MESSAGE RECEIVED ===');
        console.log('📩 [ADMIN] Data:', data);
        
        if (data.message) {
            console.log('📩 [ADMIN] Message ID:', data.message.id);
            console.log('📩 [ADMIN] Conversation ID:', data.message.conversation_id);
            console.log('📩 [ADMIN] Sender Type:', data.message.sender_type);
            console.log('📩 [ADMIN] Message:', data.message.message);
            
            // Check if this is for the current conversation
            if (currentConversationId && data.message.conversation_id === currentConversationId) {
                console.log('✅ [ADMIN] Message for current conversation - RELOADING!');
                loadMessages(currentConversationId);
            } else {
                console.log('ℹ️ [ADMIN] Message for different conversation');
            }
            
            // Always reload conversations list to update unread count
            console.log('🔄 [ADMIN] Reloading conversations list...');
            loadConversations();
        }
    });
    
    console.log('✅ [ADMIN] Pusher setup complete!');
}

// =========================================================================
// EXISTING FUNCTIONS (GIỮ NGUYÊN)
// =========================================================================

// Load conversations
async function loadConversations(filter = 'all') {
    try {
        const response = await fetch(`/admin/public/api/support/conversations?filter=${filter}`);
        const data = await response.json();
        
        if (data.success) {
            renderConversations(data.conversations);
        }
    } catch (error) {
        console.error('Load error:', error);
    }
}

// Render conversations list
function renderConversations(conversations) {
    const container = document.getElementById('conversations-list');
    
    if (!conversations || conversations.length === 0) {
        container.innerHTML = '<p class="text-center text-muted p-3">Chưa có conversation nào</p>';
        return;
    }
    
    container.innerHTML = conversations.map(conv => `
        <div class="conversation-item ${conv.conversation_id === currentConversationId ? 'active' : ''}" 
             data-id="${conv.conversation_id}" 
             onclick="selectConversation('${conv.conversation_id}')">
            <div class="d-flex align-items-center p-3">
                <img src="${conv.customer_avatar || '/images/default-avatar.png'}" 
                     class="rounded-circle me-3" 
                     width="50" height="50">
                <div class="flex-grow-1">
                    <div class="fw-bold">${conv.customer_name}</div>
                    <div class="text-muted small text-truncate">${conv.last_message || 'Chưa có tin nhắn'}</div>
                </div>
                <div class="text-muted small">${formatTime(conv.last_message_at)}</div>
            </div>
        </div>
    `).join('');
}

// Select conversation
async function selectConversation(conversationId) {
    console.log('📌 [ADMIN] Selecting conversation:', conversationId);
    currentConversationId = conversationId;
    
    // Update UI
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.toggle('active', item.dataset.id === conversationId);
    });
    
    // Load messages
    await loadMessages(conversationId);
}

// Load messages
async function loadMessages(conversationId) {
    try {
        console.log('📥 [ADMIN] Loading messages for:', conversationId);
        
        const response = await fetch(`/admin/public/api/support/conversations/${conversationId}/messages`);
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ [ADMIN] Loaded', data.messages.length, 'messages');
            renderMessages(data.messages);
        }
    } catch (error) {
        console.error('Load messages error:', error);
    }
}

// Render messages
function renderMessages(messages) {
    const container = document.getElementById('messages-container');
    
    container.innerHTML = messages.map(msg => `
        <div class="message ${msg.sender_type === 'employee' ? 'message-employee' : 'message-customer'}">
            <div class="d-flex mb-2">
                <img src="${msg.sender_avatar || '/images/default-avatar.png'}" 
                     class="rounded-circle me-2" 
                     width="32" height="32">
                <div>
                    <div class="fw-bold small">${msg.sender_name}</div>
                    <div class="message-bubble">${escapeHtml(msg.message)}</div>
                    <div class="text-muted small">${formatTime(msg.created_at)}</div>
                </div>
            </div>
        </div>
    `).join('');
    
    scrollToBottom();
}

// Send message
async function sendMessage() {
    const input = document.getElementById('message-input');
    const message = input.value.trim();
    
    if (!message || !currentConversationId) return;
    
    console.log('📤 [ADMIN] Sending message...');
    
    try {
        const response = await fetch('/admin/public/api/support/messages', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                conversation_id: currentConversationId,
                message: message,
                employee_id: window.currentEmployeeId || 1  // Get from global var
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ [ADMIN] Message sent successfully');
            input.value = '';
            await loadMessages(currentConversationId);
        }
    } catch (error) {
        console.error('Send error:', error);
    }
}

// Utility functions
function formatTime(datetime) {
    if (!datetime) return '';
    const date = new Date(datetime);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// =========================================================================
// ✅ INITIALIZATION - CẬP NHẬT ĐỂ THÊM PUSHER
// =========================================================================
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 [ADMIN] Support Chat Initialized');
    
    // ✅ INITIALIZE PUSHER FIRST
    if (typeof Pusher !== 'undefined') {
        console.log('✅ [ADMIN] Pusher library found, initializing...');
        initializePusher();
    } else {
        console.error('❌ [ADMIN] Pusher library not loaded!');
        console.error('❌ [ADMIN] Add this to your HTML: <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>');
    }
    
    // Load initial data
    loadConversations();
    
    // Auto refresh (backup for when Pusher fails)
    setInterval(() => {
        if (currentConversationId) {
            console.log('🔄 [ADMIN] Auto-refresh (polling backup)');
            loadMessages(currentConversationId);
        }
    }, 30000); // Every 30 seconds (reduced from 5 seconds since we have Pusher)
    
    // Send on enter
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    console.log('✅ [ADMIN] Setup complete!');
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (adminChannel) {
        console.log('🔌 [ADMIN] Unsubscribing from channel...');
        adminChannel.unbind_all();
        adminChannel.unsubscribe();
    }
    
    if (pusherInstance) {
        console.log('🔌 [ADMIN] Disconnecting Pusher...');
        pusherInstance.disconnect();
    }
});