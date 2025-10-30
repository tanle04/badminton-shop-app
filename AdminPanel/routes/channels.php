<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Employee; // BẮT BUỘC: Dòng này đã được thêm
use Illuminate\Support\Facades\Log; // <-- THÊM DÒNG NÀY

Log::info('File routes/channels.php ĐÃ ĐƯỢC TẢI.'); // <-- THÊM DÒNG NÀY
/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Kênh mặc định (không cần sửa)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('employee.chat.{id}', function (Employee $user, $id) {
    return (int) $user->employeeID === (int) $id;
}, ['guards' => ['admin']]); // <--- THÊM DÒNG NÀY