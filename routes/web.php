<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return Inertia::render('homePage', [

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
