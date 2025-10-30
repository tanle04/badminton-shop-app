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
                {{-- ĐÃ SỬA LỖI KHOẢNG TRẮNG: 'admin' --}}
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
                    <div class="text-center text-muted p-3">Vui lòng chọn một nhân viên để bắt đầu.</div>
                </div>
            </div>
            <div class="card-footer">
                <form id="chat-form">
                    <div class="input-group">
                        <input type="text" name="message" id="message-input" placeholder="Nhập tin nhắn..." class="form-control" required disabled>
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

<script src="https://cdn.jsdelivr/npm/axios/dist/axios.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // ĐÃ SỬA LỖI KHOẢNG TRẮNG: 'admin'
        const currentEmployeeId = '{{ \Auth::guard('admin')->id() }}';

        const chatMessages = document.getElementById('chat-messages');
        const receiverIdInput = document.getElementById('receiver-id');
        const messageInput = document.getElementById('message-input');
        const chatForm = document.getElementById('chat-form');
        const sendButton = document.getElementById('send-button');
        let currentReceiverId = null;

        // --- Kiểm tra kết nối Echo và Lắng nghe Kênh Riêng tư của Employee hiện tại ---
        if (window.Echo) {
            console.log(`✅ Echo đã khởi tạo. Đang lắng nghe trên kênh: employee.chat.${currentEmployeeId}`);

            window.Echo.private(`employee.chat.${currentEmployeeId}`)
                .listen('message.sent', (e) => { // <-- ĐÃ SỬA: Bỏ dấu chấm
                    console.log('TIN NHẮN MỚI NHẬN ĐƯỢC (EVENT LISTENER):', e); 

                    if (e.message.sender_id.toString() === currentReceiverId) {
                        appendMessage(e.message, 'received');
                    }
                })
                .error((error) => {
                    console.error('Lỗi khi đăng ký kênh private:', error);
                });

        } else {
            console.error("Laravel Echo chưa được khởi tạo. Đã chạy npm run dev chưa?");
        }


        // --- 2. Tải danh sách Employee ---
        // ĐÃ SỬA LỖI KHOẢNG TRẮNG: 'admin.chat.employees'
        axios.get('{{ route('admin.chat.employees') }}')
            .then(response => {
                const listContainer = document.getElementById('employee-list').querySelector('ul');
                listContainer.innerHTML = '';
                response.data.forEach(employee => {
                    if (employee.employeeID.toString() !== currentEmployeeId) {
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
                console.error("Lỗi khi tải danh sách nhân viên:", error.response ? error.response.data : error.message);
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

                if (newReceiverId === currentReceiverId) {
                    return; 
                }

                currentReceiverId = newReceiverId;
                receiverIdInput.value = newReceiverId;
                document.getElementById('chat-receiver-name').textContent = `Chat với: ${newReceiverName}`;
                sendButton.disabled = false;
                messageInput.disabled = false;
                messageInput.focus();

                document.querySelectorAll('.employee-link').forEach(link => link.classList.remove('active'));
                target.classList.add('active');

                loadChatHistory(newReceiverId);
            }
        });

        // --- 4. Tải Lịch sử Chat ---
        function loadChatHistory(receiverId) {
            chatMessages.innerHTML = '<div class="text-center text-muted p-3">Đang tải lịch sử...</div>'; 

            // ĐÃ SỬA LỖI KHOẢNG TRẮNG: 'admin.chat.history'
            const historyUrl = '{{ route('admin.chat.history', ['receiverId' => 'TEMP_ID']) }}'.replace('TEMP_ID', receiverId);
            
            axios.get(historyUrl)
                .then(response => {
                    chatMessages.innerHTML = ''; 
                    if (response.data.length === 0) {
                        chatMessages.innerHTML = '<div class="text-center text-muted p-3">Chưa có tin nhắn nào.</div>';
                    } else {
                        response.data.forEach(message => {
                            const type = message.sender_id.toString() === currentEmployeeId ? 'sent' : 'received';
                            appendMessage(message, type);
                        });
                        scrollToBottom();
                    }
                })
                .catch(error => {
                    chatMessages.innerHTML = '<div class="text-center text-danger p-3">Không thể tải lịch sử chat.</div>';
                    console.error("Không thể tải lịch sử chat:", error.response ? error.response.data : error.message);
                });
        }

        // --- 5. Xử lý Gửi tin nhắn ---
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const messageText = messageInput.value.trim();
            const receiverId = receiverIdInput.value;

            if (!messageText || !receiverId) return;
            sendButton.disabled = true;

            // ĐÃ SỬA LỖI KHOẢNG TRẮNG: 'admin.chat.send'
            axios.post('{{ route('admin.chat.send') }}', { 
                receiver_id: receiverId,
                message: messageText
            })
                .then(response => {
                    appendMessage(response.data.message, 'sent');
                    messageInput.value = '';
                    scrollToBottom();
                })
                .catch(error => {
                    console.error('Lỗi khi gửi tin nhắn:', error.response.status, error.response.data);
                    alert('Gửi tin nhắn thất bại, vui lòng thử lại.');
                })
                .finally(() => {
                    sendButton.disabled = false; 
                    messageInput.focus();
                });
        });

        // --- 6. Hàm tiện ích hiển thị tin nhắn ---
        function appendMessage(message, type) {
            const placeholder = chatMessages.querySelector('.text-muted, .text-danger');
            if (placeholder) {
                placeholder.remove();
            }

            const senderName = (message.sender && message.sender.fullName) ? message.sender.fullName : 'Không rõ';
            const time = new Date(message.created_at).toLocaleTimeString('vi-VN', {
                hour: '2-digit', 
                minute: '2-digit'
            });

            const element = document.createElement('div');
            
            // ĐÃ SỬA LỖI DOMTokenList: Thêm class riêng lẻ
            element.classList.add('direct-chat-msg');
            if (type === 'sent') {
                element.classList.add('right');
            }

            // Lấy avatar
            let avatar = '{{ asset('vendor/adminlte/dist/img/avatar5.png') }}'; // Avatar mặc định
            if (message.sender && message.sender.img_url) {
                avatar = `{{ asset('storage') }}/${message.sender.img_url}`; 
            }

            element.innerHTML = `
                <div class="direct-chat-infos clearfix">
                    <span class="direct-chat-name float-${type === 'sent' ? 'right' : 'left'}">${senderName}</span>
                    <span class="direct-chat-timestamp float-${type === 'sent' ? 'left' : 'right'}">${time}</span>
                </div>
                <img class="direct-chat-img" src="${avatar}" alt="img">
                <div class="direct-chat-text">${message.message}</div>
            `;
            
            chatMessages.appendChild(element);
            scrollToBottom(); 
        }

        function scrollToBottom() {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    });
</script>
@stop