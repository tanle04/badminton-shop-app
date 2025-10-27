<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Employee; // BẮT BUỘC: Dòng này đã được thêm

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

// Kênh chat riêng tư cho Employee
Broadcast::channel('employee.chat.{id}', function (Employee $user, $id) {
    // Trả về true nếu ID của Employee đang đăng nhập khớp với ID của kênh
    return (int) $user->employeeID === (int) $id;
});
