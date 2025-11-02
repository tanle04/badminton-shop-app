@extends('adminlte::page')

@section('title', 'Hỗ trợ Khách hàng')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-headset"></i> Hỗ trợ Khách hàng</h1>
        <div id="stats-badges">
            <span class="badge badge-info mr-2">
                <i class="fas fa-inbox"></i> Chưa assign: <span id="stat-unassigned">0</span>
            </span>
            <span class="badge badge-primary mr-2">
                <i class="fas fa-tasks"></i> Của tôi: <span id="stat-assigned">0</span>
            </span>
            <span class="badge badge-danger">
                <i class="fas fa-bell"></i> Chưa đọc: <span id="stat-unread">0</span>
            </span>
        </div>
    </div>
@stop

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="row no-gutters" style="height: 75vh;">
            {{-- SIDEBAR - DANH SÁCH CUỘC HỘI THOẠI --}}
            <div class="col-md-4 border-right">
                {{-- FILTER TABS --}}
                <div class="p-3 border-bottom bg-light">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-filter="all">
                            Tất cả
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-filter="assigned">
                            Của tôi
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-filter="unassigned">
                            Chưa assign
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-filter="open">
                            Đang mở
                        </button>
                    </div>
                </div>
                
                {{-- DANH SÁCH --}}
                <div id="conversations-list" class="p-2" style="height: calc(75vh - 60px); overflow-y: auto;">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Đang tải...</p>
                    </div>
                </div>
            </div>
            
            {{-- MAIN - KHU VỰC CHAT --}}
            <div class="col-md-8 d-flex flex-column">
                {{-- HEADER --}}
                <div class="p-3 border-bottom bg-light">
                    <div id="chat-header-empty" class="text-center text-muted">
                        <i class="fas fa-comments"></i> Chọn cuộc hội thoại để bắt đầu
                    </div>
                    
                    <div id="chat-header-active" class="d-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle fa-2x text-primary mr-2"></i>
                                <div>
                                    <h5 class="mb-0" id="customer-name">---</h5>
                                    <small class="text-muted">
                                        <span id="customer-email">---</span> | 
                                        <span id="customer-phone">---</span>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-success" id="btn-assign-me" title="Assign cho tôi">
                                    <i class="fas fa-user-check"></i> Nhận
                                </button>
                                <button type="button" class="btn btn-warning" id="btn-change-priority" title="Đổi độ ưu tiên">
                                    <i class="fas fa-flag"></i>
                                </button>
                                <button type="button" class="btn btn-danger" id="btn-close-conversation" title="Đóng hội thoại">
                                    <i class="fas fa-times-circle"></i> Đóng
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- MESSAGES --}}
                <div id="messages-container" class="flex-grow-1 p-3" style="overflow-y: auto; background: #f8f9fa;">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-comments fa-3x"></i>
                        <p class="mt-3">Chọn cuộc hội thoại để xem tin nhắn</p>
                    </div>
                </div>
                
                {{-- INPUT --}}
                <div class="p-3 border-top bg-white">
                    <form id="message-form" enctype="multipart/form-data">
                        <div class="input-group">
                            <input type="file" id="file-input" class="d-none" accept="image/*,.pdf,.doc,.docx">
                            <button type="button" class="btn btn-outline-secondary" id="btn-attach">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="text" 
                                   id="message-input" 
                                   class="form-control mx-2" 
                                   placeholder="Nhập tin nhắn..."
                                   autocomplete="off"
                                   disabled>
                            <button type="button" 
                                    id="btn-send-message" 
                                    class="btn btn-primary"
                                    disabled>
                                <i class="fas fa-paper-plane"></i> Gửi
                            </button>
                        </div>
                        <small id="file-name" class="text-muted d-none mt-1"></small>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .conversation-item {
        display: flex;
        padding: 12px;
        cursor: pointer;
        border-radius: 8px;
        transition: background 0.2s;
        margin-bottom: 4px;
        border-left: 3px solid transparent;
    }
    .conversation-item:hover { background: #e9ecef; }
    .conversation-item.active { 
        background: #e3f2fd; 
        border-left-color: #2196f3;
    }
    .conversation-item.unread {
        background: #fff3e0;
        border-left-color: #ff9800;
    }
    .conversation-item .avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .conversation-item .info { flex: 1; min-width: 0; }
    .conversation-item .name { 
        font-weight: 600; 
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .conversation-item .preview { 
        font-size: 12px; 
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .conversation-item .meta {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        font-size: 11px;
        color: #999;
    }
    .conversation-item .badge-unread {
        background: #f44336;
        color: white;
        border-radius: 10px;
        padding: 2px 6px;
        font-size: 10px;
        margin-top: 4px;
    }
    
    .message-wrapper { margin-bottom: 15px; }
    .message-bubble {
        max-width: 70%;
        word-wrap: break-word;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .message-bubble.customer {
        background-color: #e3f2fd !important;
        color: #212529;
    }
    .message-bubble.employee {
        background-color: #007bff !important;
        color: white;
    }
    .message-text { font-size: 14px; line-height: 1.5; }
    .message-meta { font-size: 11px; margin-top: 4px; }
    .message-attachment {
        display: inline-block;
        padding: 8px 12px;
        background: rgba(0,0,0,0.1);
        border-radius: 4px;
        margin-top: 5px;
    }
    
    .status-badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 3px;
    }
    .status-open { background: #4caf50; color: white; }
    .status-pending { background: #ff9800; color: white; }
    .status-closed { background: #9e9e9e; color: white; }
    #messages-container {
    display: flex;
    flex-direction: column;  /* Tin nhắn từ trên xuống */
    overflow-y: auto;
}


</style>
@stop

@section('js')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>

<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const currentEmployeeId = {{ auth()->guard('admin')->id() }};

let selectedConversationId = null;
let currentFilter = 'all';
let conversations = [];
let messageHistory = [];
let selectedFile = null;

// ============================================================================
// INIT ECHO
// ============================================================================
window.Pusher = Pusher;

console.log('🚀 Initializing Echo with config:', {
    key: '{{ env('PUSHER_APP_KEY') }}',
    cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
    wsHost: '127.0.0.1',
    wsPort: 6001
});

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: '{{ env('PUSHER_APP_KEY') }}',
    cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
    wsHost: '127.0.0.1',
    wsPort: 6001,
    wssPort: 6001,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
    }
});

console.log('✅ Echo initialized:', window.Echo);

// ============================================================================
// DOM READY
// ============================================================================
$(document).ready(function() {
    loadConversations();
    loadStats();
    setupEventListeners();
    connectWebSocket();
    
    // Auto refresh stats mỗi 30s
    setInterval(loadStats, 30000);
});

// ============================================================================
// SETUP EVENT LISTENERS
// ============================================================================
function setupEventListeners() {
    // Filter buttons
    $('.btn-group button[data-filter]').on('click', function() {
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        loadConversations();
    });
    
    // Send message
    $('#btn-send-message').on('click', sendMessage);
    $('#message-input').on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    // File attachment
    $('#btn-attach').on('click', () => $('#file-input').click());
    $('#file-input').on('change', function() {
        selectedFile = this.files[0];
        if (selectedFile) {
            $('#file-name').removeClass('d-none').text('📎 ' + selectedFile.name);
        }
    });
    
    // Action buttons
    $('#btn-assign-me').on('click', assignToMe);
    $('#btn-close-conversation').on('click', closeConversation);
}

// ============================================================================
// LOAD CONVERSATIONS
// ============================================================================
function loadConversations() {
    $.ajax({
        url: '/admin/support-chat/conversations',
        method: 'GET',
        data: { filter: currentFilter },
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        success: function(data) {
            conversations = data;
            renderConversations();
        },
        error: function(xhr) {
            console.error('Load conversations failed', xhr);
        }
    });
}

function renderConversations() {
    const $list = $('#conversations-list');
    $list.empty();
    
    if (conversations.length === 0) {
        $list.html('<div class="text-center text-muted py-5">' +
            '<i class="fas fa-inbox fa-3x"></i>' +
            '<p class="mt-3">Không có cuộc hội thoại</p>' +
        '</div>');
        return;
    }
    
    conversations.forEach(function(conv) {
        const initials = conv.customer.fullName.split(' ').map(n => n[0]).join('').toUpperCase();
        const lastMessage = conv.latest_message;
        const preview = lastMessage ? (lastMessage.sender_type === 'customer' ? '👤 ' : '👨‍💼 ') + lastMessage.message : 'Chưa có tin nhắn';
        const time = lastMessage ? new Date(lastMessage.created_at).toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'}) : '';
        
        const statusClass = 'status-' + conv.status;
        const unreadBadge = conv.unread_count > 0 ? `<span class="badge-unread">${conv.unread_count}</span>` : '';
        const isUnread = conv.unread_count > 0 ? 'unread' : '';
        
        const html = `<div class="conversation-item ${isUnread}" data-id="${conv.conversation_id}">
            <div class="avatar">${initials}</div>
            <div class="info">
                <div class="name">${conv.customer.fullName}</div>
                <div class="preview">${preview.substring(0, 40)}...</div>
                <span class="status-badge ${statusClass}">${conv.status}</span>
            </div>
            <div class="meta">
                <span>${time}</span>
                ${unreadBadge}
            </div>
        </div>`;
        
        $list.append(html);
    });
    
    $('.conversation-item').on('click', function() {
        selectConversation($(this).data('id'));
    });
}

// ============================================================================
// SELECT CONVERSATION
// ============================================================================
function selectConversation(conversationId) {
    selectedConversationId = conversationId;
    
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-id="${conversationId}"]`).addClass('active');
    
    loadConversationHistory(conversationId);
    
    // Enable input
    $('#message-input, #btn-send-message, #btn-attach').prop('disabled', false);
    
    // Show header
    $('#chat-header-empty').addClass('d-none');
    $('#chat-header-active').removeClass('d-none');
    
    // Update header info
    const conv = conversations.find(c => c.conversation_id === conversationId);
    if (conv) {
        $('#customer-name').text(conv.customer.fullName);
        $('#customer-email').text(conv.customer.email);
        $('#customer-phone').text(conv.customer.phone || 'N/A');
    }
}

// ============================================================================
// LOAD HISTORY
// ============================================================================
function loadConversationHistory(conversationId) {
    $.ajax({
        url: `/admin/support-chat/conversation/${conversationId}/history`,
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        success: function(data) {
            messageHistory = data.messages;
            renderMessages();
            markAsRead(conversationId);
        }
    });
}

function renderMessages() {
    const $container = $('#messages-container');
    $container.empty();
    
    if (messageHistory.length === 0) {
        $container.html('<div class="text-center text-muted py-5">' +
            '<i class="fas fa-comments fa-3x"></i>' +
            '<p class="mt-3">Chưa có tin nhắn</p>' +
        '</div>');
        return;
    }
    
    messageHistory.forEach(msg => appendMessage(msg, false));
    scrollToBottom();
}
function appendMessage(msg, shouldScroll = true) {
    const $container = $('#messages-container');
    $container.find('.text-center').remove();
    
    const isEmployee = msg.sender_type === 'employee';
    
    // ✅ FIX: Thêm class wrapper
    const wrapperClass = isEmployee ? 'employee' : 'customer';
    
    const html = `
        <div class="message-wrapper ${wrapperClass}">
            <div class="message-bubble ${isEmployee ? 'employee' : 'customer'} p-2 rounded">
                <div class="message-text">${escapeHtml(msg.message)}</div>
                ${msg.attachment_path ? `
                    <div class="message-attachment mt-2">
                        <i class="fas fa-paperclip"></i> 
                        <a href="/storage/${msg.attachment_path}" target="_blank">
                            ${msg.attachment_name || 'Attachment'}
                        </a>
                    </div>
                ` : ''}
            </div>
            <div class="message-meta small text-muted mt-1">
                ${msg.sender?.fullName || 'Unknown'} • ${formatTime(msg.created_at)}
            </div>
        </div>
    `;
    
    // ✅ APPEND ở cuối (tin mới nhất ở dưới cùng)
    $container.append(html);
    
    if (shouldScroll) scrollToBottom();
}

function scrollToBottom() {
    const $container = $('#messages-container');
    $container.scrollTop($container[0].scrollHeight);
}
/**
 * Escape HTML để tránh XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format thời gian
 */
function formatTime(datetime) {
    if (!datetime) return '';
    const date = new Date(datetime);
    const now = new Date();
    const diff = now - date;
    
    // Vừa xong
    if (diff < 60000) return 'Vừa xong';
    
    // X phút trước
    if (diff < 3600000) {
        const minutes = Math.floor(diff / 60000);
        return `${minutes} phút trước`;
    }
    
    // Hôm nay
    if (date.toDateString() === now.toDateString()) {
        return date.toLocaleTimeString('vi-VN', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
    
    // Hôm qua hoặc cũ hơn
    return date.toLocaleDateString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ============================================================================
// SEND MESSAGE
// ============================================================================
function sendMessage() {
    const message = $('#message-input').val().trim();
    
    if (!message && !selectedFile) return;
    if (!selectedConversationId) return;
    
    const $input = $('#message-input');
    const $button = $('#btn-send-message');
    
    $input.prop('disabled', true);
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    const formData = new FormData();
    formData.append('conversation_id', selectedConversationId);
    formData.append('message', message);
    
    if (selectedFile) {
        formData.append('attachment', selectedFile);
    }
    
    $.ajax({
        url: '/admin/support-chat/send',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                appendMessage(response.message, true);
                messageHistory.push(response.message);
                
                $input.val('').prop('disabled', false).focus();
                $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Gửi');
                
                selectedFile = null;
                $('#file-input').val('');
                $('#file-name').addClass('d-none');
            }
        },
        error: function(xhr) {
            alert('Lỗi gửi tin nhắn: ' + xhr.responseText);
            $input.prop('disabled', false);
            $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Gửi');
        }
    });
}

// ============================================================================
// ACTIONS
// ============================================================================
function assignToMe() {
    if (!selectedConversationId) return;
    
    $.ajax({
        url: `/admin/support-chat/conversation/${selectedConversationId}/assign`,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        data: { employee_id: currentEmployeeId },
        success: function() {
            toastr.success('Đã nhận cuộc hội thoại');
            loadConversations();
            loadStats();
        }
    });
}

function closeConversation() {
    if (!selectedConversationId) return;
    
    if (!confirm('Đóng cuộc hội thoại này?')) return;
    
    $.ajax({
        url: `/admin/support-chat/conversation/${selectedConversationId}/close`,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        success: function() {
            toastr.success('Đã đóng cuộc hội thoại');
            loadConversations();
            loadStats();
            
            selectedConversationId = null;
            $('#message-input, #btn-send-message, #btn-attach').prop('disabled', true);
            $('#chat-header-active').addClass('d-none');
            $('#chat-header-empty').removeClass('d-none');
            $('#messages-container').html('<div class="text-center text-muted py-5">' +
                '<i class="fas fa-comments fa-3x"></i>' +
                '<p class="mt-3">Chọn cuộc hội thoại để xem tin nhắn</p>' +
            '</div>');
        }
    });
}

function markAsRead(conversationId) {
    $.ajax({
        url: `/admin/support-chat/conversation/${conversationId}/mark-read`,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN }
    });
}

// ============================================================================
// STATS
// ============================================================================
function loadStats() {
    $.ajax({
        url: '/admin/support-chat/stats',
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        success: function(stats) {
            $('#stat-unassigned').text(stats.unassigned);
            $('#stat-assigned').text(stats.assigned_to_me);
            $('#stat-unread').text(stats.total_unread);
        }
    });
}

// ============================================================================
// WEBSOCKET
// ============================================================================
function connectWebSocket() {
    console.log('🔌 Connecting to WebSocket...');
    
    // ✅ THÊM: Check Echo exists
    if (!window.Echo) {
        console.error('❌ Echo not initialized!');
        return;
    }
    
    console.log('📡 Echo object:', window.Echo);
    
    const channel = window.Echo.private('admin.support.notifications');
    
    console.log('📡 Channel:', channel);
    
    // ✅ THÊM: Listen for subscription success
    channel.subscribed(() => {
        console.log('✅ Successfully subscribed to admin.support.notifications');
    });
    
    // ✅ THÊM: Listen for subscription error
    channel.error((error) => {
        console.error('❌ Channel subscription error:', error);
    });
    
    // ✅ Listen for messages
    channel.listen('.support.message.sent', function(event) {
        console.log('📩 NEW MESSAGE RECEIVED!', event);
        console.log('Message:', event.message);
        console.log('Conversation ID:', event.message.conversation_id);
        console.log('Current Conversation ID:', selectedConversationId);
        
        // Reload conversations
        loadConversations();
        loadStats();
        
        // If current conversation, append message
        if (event.message.conversation_id === selectedConversationId) {
            if (event.message.sender_type === 'customer') {
                console.log('✅ Appending customer message to current conversation');
                appendMessage(event.message, true);
                messageHistory.push(event.message);
                markAsRead(selectedConversationId);
            } else {
                console.log('ℹ️ Skipping employee message (already displayed)');
            }
        } else {
            console.log('💬 Message for different conversation');
            toastr.info(`Tin nhắn mới từ ${event.message.sender.fullName}`);
        }
    });
}
</script>
@stop