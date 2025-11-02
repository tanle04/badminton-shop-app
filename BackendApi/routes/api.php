use App\Http\Controllers\Api\SupportController;

// Support Chat routes (Yêu cầu authentication)
Route::middleware(['auth:sanctum'])->prefix('v1/support')->group(function () {
    Route::post('/init', [SupportController::class, 'initConversation']);
    Route::post('/send', [SupportController::class, 'sendMessage']);
    Route::get('/messages', [SupportController::class, 'getMessages']);
});