<?php

use App\Http\Controllers\Api\CryptoPayController;
use App\Http\Controllers\Api\CrypturaController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\ReferralController;
use App\Models\Games;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        $games = Games::all();

        return Inertia::render('homePage', [
            'games' => $games,

        ]);
    })->name('home');

    Route::get('bonus', function () {
        return Inertia::render('bonusPage', [
        ]);
    })->name('bonus');

    Route::get('partners', function () {
        return Inertia::render('partnersPage', [
        ]);
    })->name('partners');
});

Route::get('login', [AuthController::class, 'loginRedirect'])->name('login');
Route::get('tg/auth', [AuthController::class, 'telegramAuth'])->name('tg.auth');
Route::post('tg/auth/login', [AuthController::class, 'login'])->name('tg.auth.login');

Route::middleware('auth')->group(function () {
    Route::post('bonus/daily', [BonusController::class, 'bonusDaily'])->name('bonus.daily');
    Route::post('activate/promo', [BonusController::class, 'activatePromo'])->name('bonus.promo');
    Route::post('check/subscriptions', [BonusController::class, 'checkSubscriptions'])->name('bonus.check-subscriptions');
    Route::post('referral/claim', [ReferralController::class, 'claim'])->name('referral.claim');

    // Единый эндпоинт для создания платежа
    Route::post('/api/payment', [PaymentController::class, 'createPayment'])->name('payment.create');

    // CryptoPay роуты
    Route::prefix('api/crypto-pay')->group(function () {
        Route::get('/status/{paymentId}', [CryptoPayController::class, 'getStatus'])->name('crypto-pay.status');
        Route::get('/payments', [CryptoPayController::class, 'getPayments'])->name('crypto-pay.payments');
    });

    // Cryptura роуты
    Route::prefix('api/cryptura')->group(function () {
        Route::get('/status/{paymentId}', [CrypturaController::class, 'getStatus'])->name('cryptura.status');
        Route::get('/payments', [CrypturaController::class, 'getPayments'])->name('cryptura.payments');
    });

    // Выводы
    Route::prefix('api/withdrawal')->group(function () {
        Route::post('/', [WithdrawalController::class, 'create'])->name('withdrawal.create');
        Route::get('/', [WithdrawalController::class, 'index'])->name('withdrawal.index');
        Route::get('/{id}', [WithdrawalController::class, 'status'])->name('withdrawal.status');
        Route::post('/{id}/cancel', [WithdrawalController::class, 'cancel'])->name('withdrawal.cancel');
    });
});

// Route::get('test', function () {
//     $bank = \App\Models\Bank::first();
//     $bank->update([
//         'balance' => 500000,
//         'totalBets' => 0,
//         'totalWins' => 0,
//         'rtp' => 0,
//     ]);
//     for ($i = 0; $i < 5; $i++) {

//         $bankService = new \App\Service\BankService;
//         $rngService = new \App\Service\RngSerivce;

//         for ($i = 0; $i < 1000; $i++) {
//             $testBet = 1000;
//             $maxAllowedMultiplier = $bankService->getMaxAllowedMultiplier($bank, $testBet);
//             $randomMultiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 50);

//             $testMultiplier = $randomMultiplier;

//             $clampedMultiplier = $bankService->clampMultiplier($bank, $testMultiplier, $testBet);
//             $bankService->applyBet($bank, $testBet);
//             $bankService->applyWin($bank, $testMultiplier * $testBet);
//         }
//         for ($i = 0; $i < 500; $i++) {
//             $testBet = 500;
//             $maxAllowedMultiplier = $bankService->getMaxAllowedMultiplier($bank, $testBet);
//             $randomMultiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 50);

//             $testMultiplier = $randomMultiplier;

//             $clampedMultiplier = $bankService->clampMultiplier($bank, $testMultiplier, $testBet);
//             $bankService->applyBet($bank, $testBet);
//             $bankService->applyWin($bank, $testMultiplier * $testBet);
//         }
//         for ($i = 0; $i < 100; $i++) {
//             $testBet = 5000;
//             $maxAllowedMultiplier = $bankService->getMaxAllowedMultiplier($bank, $testBet);
//             $randomMultiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 50);

//             $testMultiplier = $randomMultiplier;

//             $clampedMultiplier = $bankService->clampMultiplier($bank, $testMultiplier, $testBet);
//             $bankService->applyBet($bank, $testBet);
//             $bankService->applyWin($bank, $testMultiplier * $testBet);
//         }
//         for ($i = 0; $i < 10; $i++) {
//             $testBet = 10000;
//             $maxAllowedMultiplier = $bankService->getMaxAllowedMultiplier($bank, $testBet);
//             $randomMultiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 50);

//             $testMultiplier = $randomMultiplier;

//             $clampedMultiplier = $bankService->clampMultiplier($bank, $testMultiplier, $testBet);
//             $bankService->applyBet($bank, $testBet);
//             $bankService->applyWin($bank, $testMultiplier * $testBet);
//         }
//     }
//     dd($bank);
// })->name('text');

require_once 'games.php';
require_once 'minedrop.php';
