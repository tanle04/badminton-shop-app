import _ from 'lodash';
window._ = _;

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * BỔ SUNG QUAN TRỌNG: Tự động thêm CSRF Token vào header của Axios
 * Điều này sẽ sửa lỗi 403 (Forbidden) khi gọi API (như load danh sách employee).
 */
let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
    console.log('✅ CSRF token đã được thiết lập cho Axios.');
} else {
    console.error('Lỗi: Không tìm thấy thẻ Meta CSRF token. Hãy đảm bảo layout của bạn có <meta name="csrf-token" content="{{ csrf_token() }}">');
}

/**
 * Echo
 */
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Log để kiểm tra biến môi trường
console.log('VITE_PUSHER_APP_KEY:', import.meta.env.VITE_PUSHER_APP_KEY);
console.log('VITE_PUSHER_APP_CLUSTER:', import.meta.env.VITE_PUSHER_APP_CLUSTER);
console.log('VITE_PUSHER_HOST:', import.meta.env.VITE_PUSHER_HOST);
console.log('VITE_PUSHER_PORT:', import.meta.env.VITE_PUSHER_PORT);
console.log('VITE_PUSHER_SCHEME:', import.meta.env.VITE_PUSHER_SCHEME);

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER, // Đã sửa từ lần trước

    wsHost: import.meta.env.VITE_PUSHER_HOST,
    wsPort: import.meta.env.VITE_PUSHER_PORT,
    wssPort: import.meta.env.VITE_PUSHER_PORT, 
    
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    
    // Chỉ định rõ authEndpoint
    authEndpoint: '/broadcasting/auth' 
});

console.log('Khởi tạo Echo thành công!', window.Echo);