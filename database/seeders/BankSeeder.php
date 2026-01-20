<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Bank::updateOrCreate(
            ['name' => 'Default Bank'],
            [
                'currency' => 'RUB',
                'default_balance' => 1000000,
                'totalBets' => 0,
                'totalWins' => 0,
                'rtp' => 0,
                'houseEdge' => 0.05,
                'maxPayoutPercent' => 0.05,
                'is_default' => true,
            ]
        );
    }
}
