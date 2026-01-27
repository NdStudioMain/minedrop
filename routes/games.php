<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('dice', function () {
    return Inertia::render('dicePage', [
    ]);
})->name('dice');

Route::get('mines', function () {
    return Inertia::render('minePage', [
    ]);
})->name('mines');

// Игровые роуты с rate limiting
Route::middleware(['auth', 'throttle:games'])->group(function () {
    Route::post('dice/play', [GameController::class, 'dicePlay'])->name('dice.play');
    Route::get('mines/state', [GameController::class, 'minesState'])->name('mines.state');
    Route::post('mines/start', [GameController::class, 'minesStart'])->name('mines.start');
    Route::post('mines/pick', [GameController::class, 'minesPick'])->name('mines.pick');
    Route::post('mines/cashout', [GameController::class, 'minesCashout'])->name('mines.cashout');
    Route::post('mines/multipliers', [GameController::class, 'minesMultipliers'])->name('mines.multipliers');
});
