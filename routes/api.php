<?php

use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/ping', function () {
        return response()->json(['pong' => true]);
    });
});
Route::post('/api/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');




