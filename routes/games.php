<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;
use App\Models\Games;

Route::get('dice', function () {
    return Inertia::render('dicePage', [
    ]);
})->name('dice');

Route::get('mines', function () {
    return Inertia::render('minePage', [
    ]);
})->name('mines');

Route::get('minedrop', function () {
    $game = Games::where('url_slug', 'minedrop')->first();
    return Inertia::render('slotPage', [
        'game' => $game
    ]);
})->name('minedrop');


Route::get('/slots/minedrop', function () {
    return view('minedrop');
})->name('slots.minedrop');
