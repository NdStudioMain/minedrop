<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;
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

require_once 'games.php';