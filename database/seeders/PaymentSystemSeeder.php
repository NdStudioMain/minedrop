<?php

namespace Database\Seeders;

use App\Models\PaymentSystem;
use Illuminate\Database\Seeder;

class PaymentSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systems = [
            [
                'code' => 'crypto_pay',
                'name' => 'CryptoPay (Telegram)',
                'icon' => '/assets/img/cryptobot.png',
                'is_active' => true,
            ],
        ];

        foreach ($systems as $system) {
            PaymentSystem::updateOrCreate(
                ['code' => $system['code']],
                $system
            );
        }
    }
}
