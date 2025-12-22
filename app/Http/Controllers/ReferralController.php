<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ReferralController extends Controller
{
    public function claim(): JsonResponse
    {
        $user = Auth::user();

        if ($user->ref_balance <= 0) {
            return response()->json([
                'message' => 'Нет средств для вывода',
            ], 422);
        }

        DB::transaction(function () use ($user) {
            $amount = $user->ref_balance;

            $user->balance += $amount;
            $user->ref_balance = 0;

            $user->save();
        });

        return response()->json([
            'message' => 'Реферальный баланс успешно зачислен',
            'balance' => $user->balance,
            'ref_balance' => 0,
        ]);
    }
}
