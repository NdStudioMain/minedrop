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
            'amount' => 'required|numeric|min:1',
            'mode' => 'required|string|in:BONUS,ANTE,NORMAL',
            'sessionID' => 'required|string',
            'currency' => 'required|string',
        ]);

        $user = $request->user();
        
        // Проверка баланса для BONUS режима
        if ($request->mode === 'BONUS') {
            $bet = $request->amount / 1000000;
            $requiredBalance = $bet * 100; // BONUS стоит в 100 раз больше
            
            if ($user->balance < $requiredBalance) {
                return response()->json([
                    'error' => 'Insufficient balance for bonus buy',
                    'required' => $requiredBalance,
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
