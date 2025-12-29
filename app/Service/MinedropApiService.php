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
        $game = Games::where('id_game', 'minedrop')->first();
        $bank = $game->bank;
        $bankService = new \App\Service\BankService();
        $rngService = new \App\Service\RngSerivce();
        $bet = $request->amount / 1000000;
        $type = $request->type;

        $maxAllowedMultiplier = $bankService->getMaxAllowedMultiplier($bank, $bet);
        if ($type == 'BONUS') {
            $multiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 2);
        } else {
            $multiplier = $rngService->generateMultiplier(0, $maxAllowedMultiplier, 50);
        }
        $win = $multiplier * $bet;

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
            $bankService->applyBet($bank, $bet);
            $bankService->applyWin($bank, $win);
            $this->user->balance -= $bet;
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