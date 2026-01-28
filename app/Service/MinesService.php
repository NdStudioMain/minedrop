<?php

namespace App\Service;

use App\Models\Bank;
use App\Models\Games;
use App\Models\MinesGame;
use App\Models\User;

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

        $isGame = MinesGame::where('user_id', $user->id)
            ->where('status', 'playing')
            ->first();
        if ($isGame) {
            throw new \Exception('Игра уже начата');
        }

        $cells = range(0, 24);
        shuffle($cells);
        $mines = array_slice($cells, 0, $mineCount);

        // Deduct bet immediately
        $user->decrement('balance', $bet);

        $game = Games::where('id_game', 'mines')->first();
        $bank = $game ? $game->bank : Bank::first();
        $this->bankService->applyBet($bank, $bet);

        // Create game record in database
        $minesGame = MinesGame::create([
            'user_id' => $user->id,
            'bet' => $bet,
            'mine_count' => $mineCount,
            'mines' => $mines,
            'revealed' => [],
            'step' => 0,
            'status' => 'playing',
        ]);

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
        $minesGame = MinesGame::where('user_id', $user->id)
            ->where('status', 'playing')
            ->first();

        if (! $minesGame) {
            throw new \Exception('No active game');
        }

        if (in_array($cellId, $minesGame->revealed ?? [])) {
            throw new \Exception('Cell already revealed');
        }

        if (in_array($cellId, $minesGame->mines)) {
            // Hit a mine!
            $minesGame->update([
                'status' => 'lost',
                'revealed' => array_merge($minesGame->revealed ?? [], [$cellId]),
            ]);

            return [
                'status' => 'lost',
                'mines' => $minesGame->mines,
                'cellId' => $cellId,
                'newBalance' => round($user->fresh()->balance, 2),
            ];
        }

        $revealed = $minesGame->revealed ?? [];
        $revealed[] = $cellId;
        $step = $minesGame->step + 1;

        $multiplier = $this->calculateMultiplier($minesGame->mine_count, $step);

        // Check bank limit
        $game = Games::where('id_game', 'mines')->first();
        $bank = $game ? $game->bank : Bank::first();
        $maxMultiplier = $this->bankService->getMaxAllowedMultiplier($bank, (float) $minesGame->bet);

        if ($multiplier > $maxMultiplier) {
            $multiplier = $maxMultiplier;
        }

        $minesGame->update([
            'revealed' => $revealed,
            'step' => $step,
        ]);

        return [
            'status' => 'playing',
            'revealed' => $revealed,
            'step' => $step,
            'multiplier' => round($multiplier, 2),
            'nextMultiplier' => $this->calculateMultiplier($minesGame->mine_count, $step + 1),
            'multipliers' => $this->getAllMultipliers($bank, (float) $minesGame->bet, $minesGame->mine_count),
        ];
    }

    public function getState(User $user): ?array
    {
        $minesGame = MinesGame::where('user_id', $user->id)
            ->where('status', 'playing')
            ->first();

        if (! $minesGame) {
            return null;
        }

        $game = Games::where('id_game', 'mines')->first();
        $bank = $game ? $game->bank : Bank::first();

        $multiplier = $minesGame->step > 0
            ? $this->calculateMultiplier($minesGame->mine_count, $minesGame->step)
            : 0;

        return [
            'status' => 'playing',
            'bet' => (float) $minesGame->bet,
            'mineCount' => $minesGame->mine_count,
            'revealed' => $minesGame->revealed ?? [],
            'step' => $minesGame->step,
            'multiplier' => round($multiplier, 2),
            'nextMultiplier' => $this->calculateMultiplier($minesGame->mine_count, $minesGame->step + 1),
            'multipliers' => $this->getAllMultipliers($bank, (float) $minesGame->bet, $minesGame->mine_count),
        ];
    }

    public function cashout(User $user)
    {
        return \DB::transaction(function () use ($user) {
            $minesGame = MinesGame::where('user_id', $user->id)
                ->where('status', 'playing')
                ->lockForUpdate()
                ->first();

            if (! $minesGame || $minesGame->step === 0) {
                throw new \Exception('Cannot cashout');
            }

            $multiplier = $this->calculateMultiplier($minesGame->mine_count, $minesGame->step);

            $game = Games::where('id_game', 'mines')->first();
            $bank = $game ? $game->bank : Bank::first();
            $maxMultiplier = $this->bankService->getMaxAllowedMultiplier($bank, (float) $minesGame->bet);
            $multiplier = min($multiplier, $maxMultiplier);

            $winAmount = $minesGame->bet * $multiplier;

            $this->bankService->applyWin($bank, $winAmount);
            $user->increment('balance', $winAmount);

            $mines = $minesGame->mines;
            $minesGame->update(['status' => 'won']);

            return [
                'status' => 'won',
                'winAmount' => round($winAmount, 2),
                'multiplier' => round($multiplier, 2),
                'mines' => $mines,
                'newBalance' => round($user->fresh()->balance, 2),
            ];
        });
    }

    private function calculateMultiplier(int $mines, int $step): float
    {
        if ($step <= 0) {
            return 1.0;
        }

        $p = 1.0;
        for ($i = 0; $i < $step; $i++) {
            $p *= (25 - $mines - $i) / (25 - $i);
        }

        if ($p <= 0) {
            return 0;
        }

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
                'multiplier' => min($m, $maxMultiplier),
            ];
        }

        return $multipliers;
    }
}
