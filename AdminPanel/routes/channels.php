<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Employee;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

Log::info('✅ File routes/channels.php LOADED');

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Default channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Employee chat channel
Broadcast::channel('employee.chat.{id}', function (Employee $user, $id) {
    Log::info('🔐 Auth check for employee.chat.' . $id, [
        'user_id' => $user->employeeID
    ]);
    return (int) $user->employeeID === (int) $id;
}, ['guards' => ['admin']]);

// Customer support channel
Broadcast::channel('customer.support.{customerId}', function (Customer $user, $customerId) {
    Log::info('🔐 Auth check for customer.support.' . $customerId, [
        'user_id' => $user->customerID
    ]);
    return (int) $user->customerID === (int) $customerId;
}, ['guards' => ['web']]);

// Employee support channel
Broadcast::channel('employee.support.{employeeId}', function (Employee $user, $employeeId) {
    Log::info('🔐 Auth check for employee.support.' . $employeeId, [
        'user_id' => $user->employeeID
    ]);
    return (int) $user->employeeID === (int) $employeeId;
}, ['guards' => ['admin']]);

// ✅ QUAN TRỌNG: Admin support notifications channel
Broadcast::channel('admin.support.notifications', function ($user) {
    Log::info('🔐 Auth check for admin.support.notifications', [
        'user' => $user ? get_class($user) : null,
        'user_id' => $user ? ($user->employeeID ?? $user->id ?? null) : null
    ]);
    
    // Allow all authenticated admin users
    return $user !== null;
}, ['guards' => ['admin']]);