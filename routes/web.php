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
    Route::post('api/bonus/daily', [BonusController::class, 'bonusDaily'])->name('bonus.daily');
    Route::post('api/bonus/promo', [BonusController::class, 'activatePromo'])->name('bonus.promo');
    Route::post('api/bonus/check-subscriptions', [BonusController::class, 'checkSubscriptions'])->name('bonus.check-subscriptions');
    Route::post('api/referral/claim', [ReferralController::class, 'claim'])->name('referral.claim');
});

require_once 'games.php';
