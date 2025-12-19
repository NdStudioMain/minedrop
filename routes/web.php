<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

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

require __DIR__.'/settings.php';
