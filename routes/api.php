<?php

use App\Http\Controllers\Api\CryptoPayController;
use App\Http\Controllers\Api\CrypturaController;
use App\Http\Controllers\Api\StarPaymentController;
use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'throttle:api'])->group(function () {
    Route::get('/ping', function () {
        return response()->json(['pong' => true]);
    });

    // Webhooks без rate limiting (они защищены подписью)
    Route::withoutMiddleware('throttle:api')->group(function () {
        Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');
        Route::post('/crypto-pay/webhook', [CryptoPayController::class, 'webhook'])->name('crypto-pay.webhook');
        Route::post('/cryptura/callback', [CrypturaController::class, 'callback'])->name('cryptura.callback');
    });

    // CryptoPay публичные роуты
    Route::get('/crypto-pay/rates', [CryptoPayController::class, 'getExchangeRates'])->name('crypto-pay.rates');
    Route::get('/crypto-pay/methods', [CryptoPayController::class, 'getPaymentMethods'])->name('crypto-pay.methods');

    // Stars публичные роуты
    Route::get('/stars/info', [StarPaymentController::class, 'getInfo'])->name('stars.info');
    Route::get('/stars/convert', [StarPaymentController::class, 'convert'])->name('stars.convert');
});
