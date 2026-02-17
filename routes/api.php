<?php

use App\Http\Controllers\Api\CryptoPayController;
use App\Http\Controllers\Api\P2ParadiseController;
use App\Http\Controllers\Api\StarPaymentController;
use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/ping', function () {
        return response()->json(['pong' => true]);
    });

    Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');

    // CryptoPay публичные роуты
    Route::post('/crypto-pay/webhook', [CryptoPayController::class, 'webhook'])->name('crypto-pay.webhook');
    Route::get('/crypto-pay/rates', [CryptoPayController::class, 'getExchangeRates'])->name('crypto-pay.rates');
    Route::get('/crypto-pay/methods', [CryptoPayController::class, 'getPaymentMethods'])->name('crypto-pay.methods');

    // P2Paradise (НСПК / СБП) публичные роуты
    Route::post('/p2paradise/callback', [P2ParadiseController::class, 'callback'])->name('p2paradise.callback');

    // Stars публичные роуты
    Route::get('/stars/info', [StarPaymentController::class, 'getInfo'])->name('stars.info');
    Route::get('/stars/convert', [StarPaymentController::class, 'convert'])->name('stars.convert');
});
