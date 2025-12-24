<?php

namespace App\Service;

use App\Models\Bank;
use Illuminate\Support\Facades\DB;

class BankService
{
    public function getMaxAllowedMultiplier(Bank $bank, float $bet): float
    {
        $availableBank = $bank->default_balance;
        $maxPayout = $availableBank * $bank->maxPayoutPercent;

        if ($bet <= 0) {
            return 0;
        }

        return round($maxPayout / $bet, 2);
    }

    public function clampMultiplier(
        Bank $bank,
        float $multiplier,
        float $bet
    ): float {
        $maxMultiplier = $this->getMaxAllowedMultiplier($bank, $bet);

        return min($multiplier, $maxMultiplier);
    }

    public function applyBet(Bank $bank, float $bet): void
    {
        $bank->increment('totalBets', $bet);

        // house edge сразу остаётся в банке
        $bank->increment(
            'default_balance',
            $bet * $bank->houseEdge
        );
    }

    public function applyWin(Bank $bank, float $win): void
    {
        $bank->increment('totalWins', $win);
        $bank->decrement('default_balance', $win);

        $this->recalcRTP($bank);
    }

    protected function recalcRTP(Bank $bank): void
    {
        if ($bank->totalBets <= 0) {
            return;
        }

        $rtp = ($bank->totalWins / $bank->totalBets) * 100;
        $bank->update(['rtp' => round($rtp, 2)]);
    }
}
