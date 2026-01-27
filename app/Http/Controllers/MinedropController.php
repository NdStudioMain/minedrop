<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Service\MinedropApiService;
use Illuminate\Http\Request;

class MinedropController extends Controller
{
    public function authenticate(Request $request)
    {
        $user = $request->user();
        $minedropApiService = new MinedropApiService($user);
        $authenticate = $minedropApiService->authenticate($request);
        return $authenticate;
    }

    public function play(Request $request)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'mode' => 'required|string|in:BONUS,ANTE,NORMAL',
            'sessionID' => 'required|string',
            'currency' => 'required|string',
        ]);

        $user = $request->user();
        $bet = $request->amount / 1000000; // Конвертируем из микросуммы

        // Валидация суммы для BONUS режима
        if ($request->mode === 'BONUS') {
            $bonusBet = $bet * 100; // BONUS стоит в 100 раз больше
            if ($user->balance < $bonusBet) {
                return response()->json([
                    'error' => 'Insufficient balance for bonus buy',
                    'required' => $bonusBet,
                    'current' => $user->balance,
                ], 400);
            }

            // Ограничение максимальной суммы для BONUS (например, 10000 RUB)
            $maxBonusBet = 10000;
            if ($bonusBet > $maxBonusBet) {
                return response()->json([
                    'error' => 'Maximum bonus buy amount exceeded',
                    'max' => $maxBonusBet,
                    'requested' => $bonusBet,
                ], 400);
            }

            // Минимальная сумма для BONUS (например, 100 RUB)
            $minBonusBet = 100;
            if ($bonusBet < $minBonusBet) {
                return response()->json([
                    'error' => 'Minimum bonus buy amount not met',
                    'min' => $minBonusBet,
                    'requested' => $bonusBet,
                ], 400);
            }
        } else {
            // Проверка баланса для обычной игры
            if ($user->balance < $bet) {
                return response()->json([
                    'error' => 'Insufficient balance',
                    'required' => $bet,
                    'current' => $user->balance,
                ], 400);
            }
        }

        $minedropApiService = new MinedropApiService($user);
        $result = $minedropApiService->playGame($request);

        $result['updated_balance'] = $user->fresh()->balance;

        return $result;
    }

    public function balance(Request $request)
    {
        $user = $request->user();
        $minedropApiService = new MinedropApiService($user);
        $balance = $minedropApiService->balance($request);
        return $balance;
    }

    public function endRound(Request $request)
    {
        $user = $request->user();
        $minedropApiService = new MinedropApiService($user);
        $endRound = $minedropApiService->endRound($request);
        return $endRound;
    }


}
