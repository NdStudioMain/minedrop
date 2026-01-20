<?php

use App\Http\Controllers\Api\CryptoPayController;
use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/ping', function () {
        return response()->json(['pong' => true]);
    });

    Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');

    // CryptoPay вебхук (без авторизации)
    Route::post('/crypto-pay/webhook', [CryptoPayController::class, 'webhook'])->name('crypto-pay.webhook');

    // CryptoPay курсы валют (без авторизации)
    Route::get('/crypto-pay/rates', [CryptoPayController::class, 'getExchangeRates'])->name('crypto-pay.rates');

    // CryptoPay методы оплаты и курсы (без авторизации для отображения в форме)
    Route::get('/crypto-pay/methods', [CryptoPayController::class, 'getPaymentMethods'])->name('crypto-pay.methods');
});
