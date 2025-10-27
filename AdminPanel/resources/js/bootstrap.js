import _ from 'lodash';
window._ = _;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const VITE_HOST = import.meta.env.VITE_PUSHER_HOST;
const VITE_PORT = import.meta.env.VITE_PUSHER_PORT;
const VITE_SCHEME = import.meta.env.VITE_PUSHER_SCHEME;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,

    // Cấu hình Websocket cục bộ (127.0.0.1:6001)
    wsHost: VITE_HOST,
    wsPort: VITE_PORT,
    wssPort: VITE_PORT,
    
    // Bỏ qua cluster: Đây là cách Pusher nhận ra nó đang chạy trên custom host
    // Bắt buộc phải có wsHost/wsPort được định nghĩa và không bị trống trong .env
    
    enabledTransports: ['ws', 'wss'],
    
    // Đảm bảo forceTLS chỉ là true khi sử dụng https
    forceTLS: VITE_SCHEME === 'https',
    
    disableStats: true,
});