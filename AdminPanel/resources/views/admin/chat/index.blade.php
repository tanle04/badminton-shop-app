@extends('adminlte::page')

@section('title', 'Quản lý Chat Nội Bộ - DEBUG')

@section('content_header')
    <h1>
        <i class="fas fa-comments"></i> Quản lý Chat Nội Bộ
        <span class="badge badge-warning">DEBUG MODE</span>
    </h1>
@stop

@section('content')
{{-- DEBUG PANEL --}}
<div class="card card-warning mb-3">
    <div class="card-header">
        <h5 class="mb-0">🐛 Debug Panel</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Echo Status:</strong>
                <span id="debug-echo" class="badge badge-secondary">Checking...</span>
            </div>
            <div class="col-md-3">
                <strong>WebSocket:</strong>
                <span id="debug-ws" class="badge badge-secondary">Checking...</span>
            </div>
            <div class="col-md-3">
                <strong>Current User:</strong>
                <span class="badge badge-info">{{ auth()->guard('admin')->id() }}</span>
            </div>
            <div class="col-md-3">
                <strong>Selected:</strong>
                <span id="debug-selected" class="badge badge-secondary">None</span>
            </div>
        </div>
        <hr>
        <div>
            <strong>Console Logs:</strong>
            <pre id="debug-console" style="max-height: 200px; overflow-y: auto; background: #1e1e1e; color: #00ff00; padding: 10px; border-radius: 5px; font-size: 11px;"></pre>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="row no-gutters" style="height: 70vh;">
            {{-- SIDEBAR --}}
            <div class="col-md-4 border-right">
                <div class="p-3 border-bottom bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> 
                        Nhân viên ({{ auth()->guard('admin')->user()->fullName }})
                    </h5>
                </div>
                
                <div id="employee-list" class="p-2" style="height: calc(70vh - 60px); overflow-y: auto;">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Đang tải...</p>
                    </div>
                </div>
            </div>
            
            {{-- MAIN --}}
            <div class="col-md-8 d-flex flex-column">
                <div class="p-3 border-bottom bg-light">
                    <h5 class="mb-0" id="chat-header">
                        <i class="fas fa-comment-dots"></i> 
                        <span id="receiver-name">Chọn người để chat</span>
                    </h5>
                </div>
                
                <div id="messages-container" class="flex-grow-1 p-3" style="overflow-y: auto; background: #f8f9fa;">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x"></i>
                        <p class="mt-3">Chọn nhân viên để bắt đầu trò chuyện</p>
                    </div>
                </div>
                
                <div class="p-3 border-top bg-white">
                    <form id="message-form" onsubmit="return false;">
                        <div class="input-group">
                            <input type="text" 
                                   id="message-input" 
                                   class="form-control" 
                                   placeholder="Nhập tin nhắn..."
                                   autocomplete="off">
                            <div class="input-group-append">
                                <button type="button" 
                                        id="btn-send-message" 
                                        class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Gửi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .employee-item {
        display: flex;
        align-items: center;
        padding: 12px;
        cursor: pointer;
        border-radius: 8px;
        transition: background 0.2s;
        margin-bottom: 4px;
    }
    .employee-item:hover { background: #e9ecef; }
    .employee-item.active { background: #007bff; color: white; }
    .employee-item .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 12px;
    }
    .employee-item .info { flex: 1; }
    .employee-item .name { font-weight: 600; font-size: 14px; }
    .employee-item .role { font-size: 12px; opacity: 0.8; }
    .employee-item.active .name,
    .employee-item.active .role { color: white; }
    .message-wrapper { margin-bottom: 15px; }
    .message-bubble {
        max-width: 70%;
        word-wrap: break-word;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .message-bubble.bg-primary { background-color: #007bff !important; color: white; }
    .message-bubble.bg-secondary { background-color: #e9ecef !important; color: #212529; }
    .message-text { font-size: 14px; line-height: 1.5; }
    .message-meta { font-size: 11px; margin-top: 4px; }
    .text-right .message-meta { text-align: right; }
    #message-input { border-radius: 20px; }
    #btn-send-message { border-radius: 20px; }
</style>
@stop

@section('js')
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>

<script>
// ============================================================================
// DEBUG LOGGER
// ============================================================================
const DEBUG_LOGS = [];
function debugLog(message, data) {
    const timestamp = new Date().toLocaleTimeString('vi-VN');
    const logEntry = `[${timestamp}] ${message}`;
    
    DEBUG_LOGS.push(logEntry);
    console.log(logEntry, data || '');
    
    // Update debug panel
    const $console = $('#debug-console');
    $console.text(DEBUG_LOGS.slice(-20).join('\n'));
    $console.scrollTop($console[0].scrollHeight);
}

// ============================================================================
// INIT ECHO
// ============================================================================
debugLog('🔧 Initializing Pusher...');
window.Pusher = Pusher;

debugLog('🔧 Initializing Echo...');
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: '{{ env('PUSHER_APP_KEY', 'badminton-key') }}',
    cluster: '{{ env('PUSHER_APP_CLUSTER', 'mt1') }}', // <-- ✅ THÊM DÒNG NÀY
    wsHost: '127.0.0.1',
    wsPort: 6001,
    wssPort: 6001,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    }
});

debugLog('✅ Echo initialized', window.Echo);
$('#debug-echo').removeClass('badge-secondary').addClass('badge-success').text('OK');

// ============================================================================
// CONSTANTS
// ============================================================================
const CSRF_TOKEN = '{{ csrf_token() }}';
const currentUserId = {{ auth()->guard('admin')->id() }};

debugLog('🆔 Current User ID: ' + currentUserId);

let selectedReceiverId = null;
let selectedReceiverName = null;
let messageHistory = [];

// ============================================================================
// DOM READY
// ============================================================================
$(document).ready(function() {
    debugLog('🚀 Document ready');
    
    loadEmployees();
    connectWebSocket();
    
    // Button click
    $('#btn-send-message').on('click', function(e) {
        debugLog('🖱️ Button clicked!');
        e.preventDefault();
        e.stopPropagation();
        sendMessage();
    });
    
    // Form submit prevention
    $('#message-form').on('submit', function(e) {
        debugLog('📝 Form submit prevented');
        e.preventDefault();
        return false;
    });
    
    // Enter key
    $('#message-input').on('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            debugLog('⌨️ Enter pressed');
            e.preventDefault();
            sendMessage();
            return false;
        }
    });
});

// ============================================================================
// LOAD EMPLOYEES
// ============================================================================
function loadEmployees() {
    debugLog('📥 Loading employees...');
    
    $.ajax({
        url: '/admin/chat/employees',
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        success: function(employees) {
            debugLog('✅ Employees loaded: ' + employees.length, employees);
            
            const $list = $('#employee-list');
            $list.empty();
            
            employees.forEach(function(emp) {
                const avatar = emp.img_url ? '/storage/' + emp.img_url : '/images/default-avatar.png';
                
                const html = '<div class="employee-item" data-id="' + emp.employeeID + '" data-name="' + emp.fullName + '">' +
                    '<img src="' + avatar + '" class="avatar" onerror="this.src=\'/images/default-avatar.png\'">' +
                    '<div class="info">' +
                        '<div class="name">' + emp.fullName + '</div>' +
                        '<div class="role">' + emp.role + '</div>' +
                    '</div>' +
                '</div>';
                
                $list.append(html);
            });
            
            $('.employee-item').on('click', function() {
                selectEmployee($(this).data('id'), $(this).data('name'));
            });
        },
        error: function(xhr) {
            debugLog('❌ Load employees failed', xhr);
            $('#employee-list').html('<div class="alert alert-danger m-2">Lỗi tải danh sách!</div>');
        }
    });
}

// ============================================================================
// SELECT EMPLOYEE
// ============================================================================
function selectEmployee(empId, empName) {
    debugLog('👤 Selected: ' + empId + ' - ' + empName);
    
    selectedReceiverId = empId;
    selectedReceiverName = empName;
    
    $('#debug-selected').text(empName);
    
    $('.employee-item').removeClass('active');
    $('.employee-item[data-id="' + empId + '"]').addClass('active');
    $('#receiver-name').text('Chat với: ' + empName);
    
    loadChatHistory(empId);
}

// ============================================================================
// LOAD CHAT HISTORY
// ============================================================================
function loadChatHistory(receiverId) {
    debugLog('📜 Loading history for: ' + receiverId);
    
    $.ajax({
        url: '/admin/chat/history/' + receiverId,
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        success: function(messages) {
            debugLog('✅ History loaded: ' + messages.length + ' messages', messages);
            messageHistory = messages;
            renderMessages();
        },
        error: function(xhr) {
            debugLog('❌ History load failed', xhr);
        }
    });
}

// ============================================================================
// RENDER MESSAGES
// ============================================================================
function renderMessages() {
    debugLog('🎨 Rendering ' + messageHistory.length + ' messages');
    
    const $container = $('#messages-container');
    $container.empty();
    
    if (messageHistory.length === 0) {
        $container.html('<div class="text-center text-muted py-5">' +
            '<i class="fas fa-comments fa-3x"></i>' +
            '<p class="mt-3">Chưa có tin nhắn</p>' +
        '</div>');
        return;
    }
    
    messageHistory.forEach(function(msg) {
        appendMessage(msg, false);
    });
    
    scrollToBottom();
}

function appendMessage(msg, shouldScroll) {
    if (typeof shouldScroll === 'undefined') shouldScroll = true;
    
    debugLog('➕ Appending message from: ' + (msg.sender?.fullName || 'Unknown'));
    debugLog('  Sender ID: ' + msg.sender_id + ', Current User: ' + currentUserId);
    
    const $container = $('#messages-container');
    $container.find('.text-center').remove();
    
    // ✅ QUAN TRỌNG: So sánh sender_id với currentUserId
    const isMine = (msg.sender_id === currentUserId);
    const align = isMine ? 'right' : 'left';
    const bg = isMine ? 'bg-primary' : 'bg-secondary';
    const name = (msg.sender && msg.sender.fullName) ? msg.sender.fullName : 'Unknown';
    
    debugLog('  Is mine: ' + isMine + ' (align: ' + align + ')');
    
    const date = new Date(msg.created_at);
    const time = date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
    
    const escapedMessage = $('<div>').text(msg.message).html();
    
    const html = '<div class="message-wrapper text-' + align + '">' +
        '<div class="message-bubble ' + bg + ' d-inline-block p-2 rounded">' +
            '<div class="message-text">' + escapedMessage + '</div>' +
        '</div>' +
        '<div class="message-meta text-muted small">' + name + ' • ' + time + '</div>' +
    '</div>';
    
    $container.append(html);
    
    if (shouldScroll) scrollToBottom();
}

function scrollToBottom() {
    const $container = $('#messages-container');
    $container.scrollTop($container[0].scrollHeight);
}

// ============================================================================
// SEND MESSAGE
// ============================================================================
function sendMessage() {
    const message = $('#message-input').val().trim();
    
    debugLog('📤 sendMessage() called');
    debugLog('  Message: "' + message + '"');
    debugLog('  Receiver ID: ' + selectedReceiverId);
    debugLog('  Current User ID: ' + currentUserId);
    
    if (!message) {
        debugLog('⚠️ Empty message');
        return;
    }
    
    if (!selectedReceiverId) {
        debugLog('⚠️ No receiver selected');
        alert('Vui lòng chọn người nhận!');
        return;
    }
    
    debugLog('📤 Sending AJAX request...');
    
    const $input = $('#message-input');
    const $button = $('#btn-send-message');
    
    $input.prop('disabled', true);
    $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    $.ajax({
        url: '/admin/chat/send',
        method: 'POST',
        headers: { 
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        data: {
            receiver_id: selectedReceiverId,
            message: message
        },
        success: function(response) {
            debugLog('✅ AJAX success', response);
            debugLog('  Response type: ' + typeof response);
            debugLog('  Response.success: ' + response.success);
            debugLog('  Response.message: ', response.message);
            
            // ✅ Kiểm tra response
            if (!response) {
                debugLog('❌ Response is null/undefined');
                $input.prop('disabled', false).focus();
                $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Gửi');
                return;
            }
            
            if (response.success !== true) {
                debugLog('❌ Response.success is not true: ' + response.success);
                alert('Lỗi: ' + (response.error || 'Không rõ lỗi'));
                $input.prop('disabled', false).focus();
                $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Gửi');
                return;
            }
            
            if (!response.message) {
                debugLog('❌ Response.message is missing');
                alert('Lỗi: Server không trả về tin nhắn');
                $input.prop('disabled', false).focus();
                $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Gửi');
                return;
            }
            
            debugLog('📨 Appending message for SENDER (me)');
            debugLog('  Message ID: ' + response.message.id);
            debugLog('  Sender ID: ' + response.message.sender_id);
            debugLog('  Receiver ID: ' + response.message.receiver_id);
            
            // ✅ Hiển thị tin nhắn cho người gửi
            appendMessage(response.message, true);
            messageHistory.push(response.message);
            
            debugLog('✅ Message appended successfully');
            
            // Clear input
            $input.val('').prop('disabled', false).focus();
            $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Gửi');
        },
        error: function(xhr, status, error) {
            debugLog('❌ AJAX failed');
            debugLog('  Status: ' + xhr.status);
            debugLog('  Status Text: ' + xhr.statusText);
            debugLog('  Error: ' + error);
            debugLog('  Response Text: ' + xhr.responseText);
            
            let errorMsg = 'Lỗi gửi tin nhắn!';
            
            try {
                const errorData = JSON.parse(xhr.responseText);
                if (errorData.message) {
                    errorMsg += '\n' + errorData.message;
                }
                if (errorData.errors) {
                    errorMsg += '\n' + JSON.stringify(errorData.errors);
                }
            } catch (e) {
                if (xhr.responseText) {
                    errorMsg += '\n' + xhr.responseText.substring(0, 200);
                }
            }
            
            alert(errorMsg);
            
            $input.prop('disabled', false).focus();
            $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Gửi');
        }
    });
}

// ============================================================================
// WEBSOCKET CONNECTION
// ============================================================================
function connectWebSocket() {
    debugLog('🔌 Connecting WebSocket...');
    
    if (typeof window.Echo === 'undefined') {
        debugLog('❌ Echo not defined!');
        $('#debug-ws').removeClass('badge-secondary').addClass('badge-danger').text('ERROR');
        return;
    }
    
    const channelName = 'employee.chat.' + currentUserId;
    debugLog('📡 Subscribing to: ' + channelName);
        
        window.Echo.private(channelName)
    .listen('.message.sent', function(event) {
        debugLog('📩 WebSocket message received!', event);
        
        // ✅ Lấy data từ event.message (không phải event trực tiếp)
        const msg = event.message || event;
        
        debugLog('  Sender ID: ' + msg.sender_id);
        debugLog('  Receiver ID: ' + msg.receiver_id);
        debugLog('  Selected ID: ' + selectedReceiverId);
        
        const relevant = (msg.sender_id === selectedReceiverId) || 
                        (msg.receiver_id === selectedReceiverId);
        debugLog('  Is relevant: ' + relevant);
        
        if (relevant) {
            debugLog('✅ Relevant! Appending...');
            appendMessage(msg, true);
            messageHistory.push(msg);
        } else {
            debugLog('ℹ️ Not relevant to current conversation');
        }
    }).error(function(err) {
            debugLog('❌ WebSocket error', err);
            $('#debug-ws').removeClass('badge-secondary').addClass('badge-danger').text('ERROR');
        });
    
    debugLog('✅ WebSocket connected');
    $('#debug-ws').removeClass('badge-secondary').addClass('badge-success').text('Connected');
}

debugLog('💬 Script loaded');
</script>
@stop