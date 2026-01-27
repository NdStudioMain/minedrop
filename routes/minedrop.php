<?php

use App\Http\Controllers\MinedropController;
use App\Models\Games;
use App\Models\User;
use App\Service\MinedropApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/slots/minedrop_game', function (Request $request) {
    $user = $request->user();
    $game = Games::where('id_game', 'minedrop')->first();
    $session = (new MinedropApiService($user))->createSession()['session_uuid'];
    return Inertia::render('slotPage', [
        'game' => $game,
        'session' => $session
    ]);
})->name('minedrop');


Route::get('/slots/minedrop', function () {
    return view('minedrop');
})->name('slots.minedrop');
Route::post('/wallet/authenticate', [MinedropController::class, 'authenticate']);
Route::post('/wallet/play', [MinedropController::class, 'play']);
Route::post('/wallet/balance', [MinedropController::class, 'balance']);
Route::post('/wallet/end-round', [MinedropController::class, 'endRound']);
// Route::get('/session/create', [SessionController::class, 'create']);