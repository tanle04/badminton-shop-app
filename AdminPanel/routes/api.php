<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Bridge\SupportBridgeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// API Bridge cho BackendApi (Bảo vệ bằng API Key)
Route::prefix('bridge/support')->middleware(['api.key'])->group(function () {
    Route::post('/init-conversation', [SupportBridgeController::class, 'initConversation']);
    Route::post('/send-message', [SupportBridgeController::class, 'sendMessage']);
    Route::get('/messages', [SupportBridgeController::class, 'getMessages']);
    Route::get('/unread-count', [SupportBridgeController::class, 'getUnreadCount']);
    Route::get('/health', [SupportBridgeController::class, 'healthCheck']);
    
    // ✅ THÊM DÒNG NÀY
    Route::post('/trigger-broadcast', [SupportBridgeController::class, 'triggerBroadcast']);
});
