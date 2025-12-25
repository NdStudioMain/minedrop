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

