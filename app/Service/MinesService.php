<?php

namespace App\Service;

use App\Models\Games;
use App\Models\User;
use App\Models\Bank;
use Illuminate\Support\Facades\Session;

class MinesService
{
    public function __construct(
        protected BankService $bankService,
        protected RngSerivce $rngService
    ) {}

    public function start(User $user, float $bet, int $mineCount)
    {
        if ($user->balance < $bet) {
            throw new \Exception('Insufficient balance');
        }

        // Generate mines
        $cells = range(0, 24);
        shuffle($cells);
        $mines = array_slice($cells, 0, $mineCount);

        $state = [
            'bet' => $bet,
            'mineCount' => $mineCount,
            'mines' => $mines,
            'revealed' => [],
            'status' => 'playing',
            'step' => 0,
        ];

        Session::put('mines_game', $state);

        // Deduct bet immediately
        $user->decrement('balance', $bet);

        $game = Games::where('id_game', 'mines')->first();
        $bank = $game ? $game->bank : Bank::first();
        $this->bankService->applyBet($bank, $bet);

        return [
            'status' => 'playing',
            'revealed' => [],
            'step' => 0,
            'nextMultiplier' => $this->calculateMultiplier($mineCount, 1),
            'multipliers' => $this->getAllMultipliers($bank, $bet, $mineCount),
            'newBalance' => round($user->fresh()->balance, 2),
        ];
    }

    public function pick(User $user, int $cellId)
    {
        $state = Session::get('mines_game');
        if (!$state || $state['status'] !== 'playing') {
            throw new \Exception('No active game');
        }

        if (in_array($cellId, $state['revealed'])) {
            throw new \Exception('Cell already revealed');
        }

        if (in_array($cellId, $state['mines'])) {
            // Hit a mine!
            $state['status'] = 'lost';
            $state['revealed'][] = $cellId;
            Session::forget('mines_game');

            return [
                'status' => 'lost',
                'mines' => $state['mines'],
                'cellId' => $cellId,
                'newBalance' => round($user->fresh()->balance, 2),
            ];
        }

        $state['revealed'][] = $cellId;
        $state['step']++;

        $multiplier = $this->calculateMultiplier($state['mineCount'], $state['step']);

        // Check bank limit
        $game = Games::where('id_game', 'mines')->first();
        $bank = $game ? $game->bank : Bank::first();
        $maxMultiplier = $this->bankService->getMaxAllowedMultiplier($bank, $state['bet']);

        if ($multiplier > $maxMultiplier) {
            $multiplier = $maxMultiplier;
        }

        Session::put('mines_game', $state);

        return [
            'status' => 'playing',
            'revealed' => $state['revealed'],
            'step' => $state['step'],
            'multiplier' => round($multiplier, 2),
            'nextMultiplier' => $this->calculateMultiplier($state['mineCount'], $state['step'] + 1),
            'multipliers' => $this->getAllMultipliers($bank, $state['bet'], $state['mineCount']),
        ];
    }

    public function cashout(User $user)
    {
        $state = Session::get('mines_game');
        if (!$state || $state['status'] !== 'playing' || $state['step'] === 0) {
            throw new \Exception('Cannot cashout');
        }

        $multiplier = $this->calculateMultiplier($state['mineCount'], $state['step']);

        $game = Games::where('id_game', 'mines')->first();
        $bank = $game ? $game->bank : Bank::first();
        $maxMultiplier = $this->bankService->getMaxAllowedMultiplier($bank, $state['bet']);
        $multiplier = min($multiplier, $maxMultiplier);

        $winAmount = $state['bet'] * $multiplier;

        $this->bankService->applyWin($bank, $winAmount);
        $user->increment('balance', $winAmount);

        Session::forget('mines_game');

        return [
            'status' => 'won',
            'winAmount' => round($winAmount, 2),
            'multiplier' => round($multiplier, 2),
            'mines' => $state['mines'],
            'newBalance' => round($user->fresh()->balance, 2),
        ];
    }

    private function calculateMultiplier(int $mines, int $step): float
    {
        if ($step <= 0) return 1.0;

        $p = 1.0;
        for ($i = 0; $i < $step; $i++) {
            $p *= (25 - $mines - $i) / (25 - $i);
        }

        if ($p <= 0) return 0;

        $multiplier = 1 / $p;

        // Apply house edge (5%)
        $multiplier *= 0.95;

        return round($multiplier, 2);
    }

    public function getAllMultipliers(Bank $bank, float $bet, int $mines): array
    {
        $multipliers = [];
        $maxMultiplier = $this->bankService->getMaxAllowedMultiplier($bank, $bet);

        for ($step = 1; $step <= (25 - $mines); $step++) {
            $m = $this->calculateMultiplier($mines, $step);
            $multipliers[] = [
                'step' => $step,
                'multiplier' => min($m, $maxMultiplier)
            ];
        }

        return $multipliers;
    }
}

