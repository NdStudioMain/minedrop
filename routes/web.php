<?php

use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BonusController;
use App\Http\Controllers\ReferralController;
use App\Models\Games;

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


Route::get('login', [AuthController::class, 'login'])->name('login');

Route::get('tg/auth', [AuthController::class, 'telegramAuth'])->name('tg.auth');
Route::post('tg/auth/login', [AuthController::class, 'telegramLogin'])->name('tg.auth.login');

Route::middleware('auth')->group(function () {
    Route::post('bonus/daily', [BonusController::class, 'bonusDaily'])->name('bonus.daily');
    Route::post('activate/promo', [BonusController::class, 'activatePromo'])->name('bonus.promo');
    Route::post('check/subscriptions', [BonusController::class, 'checkSubscriptions'])->name('bonus.check-subscriptions');
    Route::post('referral/claim', [ReferralController::class, 'claim'])->name('referral.claim');
});

Route::get('test', function () {
    for ($i = 0; $i < 1000; $i++) {
        $bank = \App\Models\Bank::first();
        $bankService = new \App\Service\BankService();
        $rngService = new \App\Service\RngSerivce();
        $testBet = 10000;
        $maxAllowedMultiplier = $bankService->getMaxAllowedMultiplier($bank, $testBet);
        $randomMultiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 50);

        $testMultiplier = $randomMultiplier;


        $clampedMultiplier = $bankService->clampMultiplier($bank, $testMultiplier, $testBet);
        $bankService->applyBet($bank, $testBet);
        $bankService->applyWin($bank, $testMultiplier * $testBet);
    }
    dd($bank);
})->name('text');

require_once 'games.php';
