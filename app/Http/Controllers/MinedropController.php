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
        $user = $request->user();
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
