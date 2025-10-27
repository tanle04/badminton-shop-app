@extends('adminlte::page')
{{-- Hoặc layout chính của bạn --}}

@section('title', 'Admin Chat')

@section('content_header')
<h1>Quản lý Chat Nội Bộ</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Nhân viên ({{\Auth::guard('admin')->user()->fullName}})</h3>
            </div>
            <div class="card-body p-0" id="employee-list">
                <ul class="nav nav-pills flex-column">
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card direct-chat direct-chat-primary">
            <div class="card-header">
                <h3 class="card-title" id="chat-receiver-name">Chọn nhân viên để chat</h3>
            </div>
            <div class="card-body">
                <div class="direct-chat-messages" id="chat-messages" style="height: 400px;">
                </div>
            </div>
            <div class="card-footer">
                <form id="chat-form">
                    <div class="input-group">
                        <input type="text" name="message" id="message-input" placeholder="Nhập tin nhắn..." class="form-control" required>
                        <input type="hidden" name="receiver_id" id="receiver-id" value=""> {{-- ID người nhận hiện tại --}}
                        <span class="input-group-append">
                            <button type="submit" id="send-button" class="btn btn-primary" disabled>Gửi</button>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
{{-- BẮT BUỘC: Tải file app.js đã biên dịch bằng Vite để khởi tạo Echo --}}
@vite(['resources/js/app.js'])

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // FIX LỖI CÚ PHÁP: Đảm bảo 'admin' nằm gọn trong dấu nháy đơn
        const currentEmployeeId = '{{ \Auth::guard('admin')->id() }}';

        const chatMessages = document.getElementById('chat-messages');
        const receiverIdInput = document.getElementById('receiver-id');
        const messageInput = document.getElementById('message-input');
        const chatForm = document.getElementById('chat-form');
        const sendButton = document.getElementById('send-button');
        let currentReceiverId = null;

        // --- Kiểm tra kết nối Echo và Lắng nghe Kênh Riêng tư của Employee hiện tại ---
        if (window.Echo) {
            window.Echo.private(`employee.chat.${currentEmployeeId}`)
                .listen('.message.sent', (e) => {
                    console.log('Tin nhắn mới nhận được:', e.message);

                    if (e.message.sender_id == currentReceiverId) {
                        appendMessage(e.message, 'received');
                    }
                    if (e.message.sender_id == currentEmployeeId && e.message.receiver_id == currentReceiverId) {
                        appendMessage(e.message, 'sent');
                    }
                });
        } else {
            console.error("Laravel Echo chưa được khởi tạo. Đã chạy npm run dev chưa?");
        }


        // --- 2. Tải danh sách Employee ---
        axios.get('{{ route('admin.chat.employees') }}')
            .then(response => {
                const listContainer = document.getElementById('employee-list').querySelector('ul');
                listContainer.innerHTML = '';
                response.data.forEach(employee => {
                    if (employee.employeeID != currentEmployeeId) {
                        const listItem = document.createElement('li');
                        listItem.className = 'nav-item';
                        listItem.innerHTML = `
                            <a href="#" class="nav-link employee-link" data-id="${employee.employeeID}" data-name="${employee.fullName}">
                                <i class="fas fa-user"></i> ${employee.fullName} (${employee.role})
                            </a>
                        `;
                        listContainer.appendChild(listItem);
                    }
                });
            })
            .catch(error => {
                console.error("Lỗi khi tải danh sách nhân viên:", error.response);
            });

        // --- 3. Xử lý khi chọn Employee ---
        document.getElementById('employee-list').addEventListener('click', function(e) {
            let target = e.target;
            while (target && !target.classList.contains('employee-link')) {
                target = target.parentElement;
            }

            if (target && target.classList.contains('employee-link')) {
                e.preventDefault();
                const newReceiverId = target.getAttribute('data-id');
                const newReceiverName = target.getAttribute('data-name');

                currentReceiverId = newReceiverId;
                receiverIdInput.value = newReceiverId;
                document.getElementById('chat-receiver-name').textContent = `Chat với: ${newReceiverName}`;
                sendButton.disabled = false;

                // Xóa active class cũ và thêm active class mới
                document.querySelectorAll('.employee-link').forEach(link => link.classList.remove('active'));
                target.classList.add('active');

                loadChatHistory(newReceiverId);
            }
        });

        // --- 4. Tải Lịch sử Chat ---
        function loadChatHistory(receiverId) {
            chatMessages.innerHTML = ''; // Xóa tin nhắn cũ

            // SỬA: Dùng hàm route() thay vì url() để đảm bảo tính chính xác
            const historyUrl = '{{ route('admin.chat.history', ['receiverId' => 'TEMP_ID']) }}'.replace('TEMP_ID', receiverId);
            
            axios.get(historyUrl)
                .then(response => {
                    response.data.forEach(message => {
                        const type = message.sender_id == currentEmployeeId ? 'sent' : 'received';
                        appendMessage(message, type);
                    });
                    scrollToBottom();
                })
                .catch(error => {
                    console.error("Không thể tải lịch sử chat:", error.response ? error.response.status + " " + error.response.statusText : 'Lỗi không xác định.');
                });
        }

        // --- 5. Xử lý Gửi tin nhắn ---
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const messageText = messageInput.value.trim();
            const receiverId = receiverIdInput.value;

            if (!messageText || !receiverId) return;

            // Sử dụng tên route chính xác: admin.chat.send
            axios.post('{{ route('admin.chat.send') }}', { 
                receiver_id: receiverId,
                message: messageText
            })
                .then(response => {
                    // Tin nhắn tự gửi, hiển thị ngay lập tức
                    appendMessage(response.data.message, 'sent');
                    messageInput.value = '';
                    scrollToBottom();
                })
                .catch(error => {
                    console.error('Lỗi khi gửi tin nhắn:', error.response); 
                });
        });

        // --- 6. Hàm tiện ích hiển thị tin nhắn ---
        function appendMessage(message, type) {
            const senderName = message.sender ? message.sender.fullName : 'Hệ thống';
            const time = new Date(message.created_at).toLocaleTimeString();

            const element = document.createElement('div');
            element.classList.add('direct-chat-msg', type === 'sent' ? 'right' : '');
            element.innerHTML = `
                <div class="direct-chat-infos clearfix">
                    <span class="direct-chat-name float-${type === 'sent' ? 'right' : 'left'}">${senderName}</span>
                    <span class="direct-chat-timestamp float-${type === 'sent' ? 'left' : 'right'}">${time}</span>
                </div>
                <div class="direct-chat-text">${message.message}</div>
            `;
            chatMessages.appendChild(element);
            scrollToBottom(); // Cuộn xuống sau khi thêm tin nhắn
        }

        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
</script>
@stop