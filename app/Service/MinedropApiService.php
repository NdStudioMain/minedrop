<?php

namespace App\Service;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class MinedropApiService
{
    private $apiUrl = config('services.minedrop.api_url');
    private $apiKey = config('services.minedrop.api_key');
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function createSession()
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->post($this->apiUrl . '/session/create', [
            'balance' => $this->user->balance,
            'currency' => 'USD',
            'youtube_mode' => false,
        ]);
        if ($response->successful()) {
            return $response->json()['session_id'];
        } else {
            throw new \Exception('Failed to create session: ' . $response->body());
        }
    }
    public function playGame($request)
    {

        $defaultParams = [
            'sessionID' => $request->sessionID,
            'amount' => $request->amount ?? 1000000,
            'currency' => 'RUB',
            'mode' => $request->mode ?? 'BASE',

        ];
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->post($this->apiUrl . '/game/play', [
            'session_id' => $request->session_id,
        ]);
    }
}