<?php

namespace App\Service;

use App\Models\Games;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        $bet = $request->amount / 1000000; // Конвертация из микрорублей в рубли
        $mode = $request->mode;

        // Проверка для BONUS режима
        if ($mode == 'BONUS') {
            $minBonusCost = 1000.0;
            $bonusCost = $bet * 100;

            if ($bonusCost < $minBonusCost) {
                throw new \Exception("Минимальная стоимость бонуса: {$minBonusCost} RUB. При ставке {$bet} RUB стоимость бонуса: {$bonusCost} RUB");
            }

            if ($this->user->balance < $bonusCost) {
                throw new \Exception("Недостаточно средств для покупки бонуса. Требуется: {$bonusCost} RUB, доступно: {$this->user->balance} RUB");
            }
        }

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
            return \DB::transaction(function () use ($response, $request, $bank, $bankService, $bet, $mode) {
                // Обновляем пользователя с блокировкой
                $this->user->refresh();
                $this->user->lockForUpdate();

                $result = $response->json();
                $multiplier = $result['round']['payoutMultiplier'];
                $win = $multiplier * $bet;

                // Определяем стоимость игры
                if ($mode == 'BONUS') {
                    $gameCost = $bet * 100;
                } elseif ($mode == "ANTE") {
                    $gameCost = $bet * 3;
                } else {
                    $gameCost = $bet;
                }

                // Проверяем баланс еще раз (на случай параллельных запросов)
                if ($this->user->balance < $gameCost) {
                    throw new \Exception("Недостаточно средств. Требуется: {$gameCost} RUB, доступно: {$this->user->balance} RUB");
                }

                // Применяем ставку к банку
                if ($mode == 'BONUS') {
                    $bankService->applyBet($bank, $bet * 100);
                } elseif ($mode == "ANTE") {
                    $bankService->applyBet($bank, $bet * 3);
                } else {
                    $bankService->applyBet($bank, $bet);
                }

                // Применяем выигрыш к банку
                $bankService->applyWin($bank, $win);

                // Обновляем баланс пользователя
                $this->user->balance -= $gameCost;
                $this->user->balance += $win;
                $this->user->save();

                return $result;
            });
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