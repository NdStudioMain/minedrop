<?php

namespace App\Service;

use App\Models\Games;
use App\Models\User;
use App\Models\Bank;

class DiceService
{
    public function __construct(
        protected BankService $bankService,
        protected RngSerivce $rngService
    ) {}

    public function play(User $user, float $bet, float $chance, string $type)
    {
        if ($user->balance < $bet) {
            throw new \Exception('Insufficient balance');
        }

        $game = Games::where('id_game', 'dice')->first();
        $bank = $game ? $game->bank : Bank::first();

        if (!$bank) {
            throw new \Exception('Bank not found');
        }

        // 1. Calculate potential win
        // Multiplier: 95 / chance (5% house edge)
        $houseEdge = $bank->houseEdge ?? 0.05;
        $multiplier = (100 - ($houseEdge * 100)) / $chance;

        $maxMultiplier = $this->bankService->getMaxAllowedMultiplier($bank, $bet);
        $multiplier = min($multiplier, $maxMultiplier);

        $winAmount = $bet * $multiplier;

        // 2. Generate roll
        $roll = $this->rngService->randomFloatBetween(0, 99.99);
        $roll = round($roll, 2);

        // 3. Determine if win
        $isWin = false;
        if ($type === 'over') {
            $isWin = $roll > (100 - $chance);
        } else {
            $isWin = $roll < $chance;
        }

        // 4. Update bank and user
        $this->bankService->applyBet($bank, $bet);

        if ($isWin) {
            $this->bankService->applyWin($bank, $winAmount);
            $user->increment('balance', $winAmount + $bet);
        } else {
            $user->decrement('balance', $bet);
        }

        return [
            'roll' => $roll,
            'isWin' => $isWin,
            'winAmount' => $isWin ? round($winAmount, 2) : 0,
            'multiplier' => $isWin ? round($multiplier, 2) : 0,
            'newBalance' => round($user->fresh()->balance, 2),
        ];
    }
}

