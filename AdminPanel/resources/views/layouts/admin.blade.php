<!-- Trong phần navbar -->
<li class="nav-item dropdown">
    <a class="nav-link" data-toggle="dropdown" href="#" id="chat-notification-icon">
        <i class="fas fa-comments"></i>
        <span class="badge badge-danger navbar-badge" id="total-unread-count" style="display:none;">0</span>
    </a>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">Tin nhắn</span>
        <div class="dropdown-divider"></div>
        <a href="{{ route('admin.chat') }}" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> Xem tất cả tin nhắn
        </a>
    </div>
</li>

<script>
// Update total unread count
function updateTotalUnreadCount() {
    $.ajax({
        url: '/admin/chat/unread-count',
        method: 'GET',
        success: function(response) {
            const count = response.unread_count || 0;
            const $badge = $('#total-unread-count');
            
            if (count > 0) {
                $badge.text(count).show();
            } else {
                $badge.hide();
            }
        }
    });
}

// Update every 30 seconds
setInterval(updateTotalUnreadCount, 30000);
updateTotalUnreadCount(); // Initial load
</script>