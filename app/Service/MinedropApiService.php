<?php

namespace App\Service;

use App\Models\Games;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class MinedropApiService
{
    private string $apiUrl;
    private string $apiKey;
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->apiUrl = config('services.minedrop.api_url');
        $this->apiKey = config('services.minedrop.api_key');
    }



    public function createSession()
    {
        $response = Http::timeout(360)->withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->get($this->apiUrl . '/session/create', [
                    'balance' => $this->user->balance,
                    'currency' => 'RUB',
                    'youtube_mode' => false,
                ]);
        if ($response->successful()) {
            return $response->json();
        } else {
            throw new \Exception('Failed to create session: ' . $response->body());
        }
    }
    public function playGame($request)
    {
        $bank = $this->user->bank;
        $bankService = new \App\Service\BankService();
        $rngService = new \App\Service\RngSerivce();
        $bet = $request->amount / 1000000;
        $mode = $request->mode;

        $maxAllowedMultiplier = $bankService->getMaxAllowedMultiplier($bank, $bet);

        if ($mode == 'BONUS') {
            $multiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 3);
        } else {
            $multiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 50);
        }


        $data = [
            'sessionID' => $request->sessionID,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'mode' => $request->mode,
            'multiplier' => $multiplier,
        ];
        $response = Http::timeout(360)->withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/wallet/play', $data);
        if ($response->successful()) {
            $result = $response->json();
            $multiplier = $result['round']['payoutMultiplier'];
            $win = $multiplier * $bet;
            if($request->mode == 'BONUS') {
                $bankService->applyBet($bank, $bet * 100);
            }
            else if($request->mode == "ANTE") {
                $bankService->applyBet($bank, $bet * 3);
            }
            else{
                $bankService->applyBet($bank, $bet);
            }
            $bankService->applyWin($bank, $win);
            if($request->mode == 'BONUS') {
                $this->user->balance -= $bet * 100;
            }
            else{
                $this->user->balance -= $bet;
            }
            $this->user->balance += $win;
            $this->user->save();
            return $response->json();
        } else {
            throw new \Exception('Failed to play game: ' . $response->body());
        }
    }
    public function endRound($request)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/wallet/end-round', $request->all());
        if ($response->successful()) {
            return $response->json();
        } else {
            return $response->body();
        }
    }
    public function balance($request)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/wallet/balance', $request->all());
        return $response->json();
    }

    public function authenticate($request)
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiUrl . '/wallet/authenticate', $request->all());
        if ($response->successful()) {
            return $response->json();
        } else {
            return $response->body();
        }
    }
}