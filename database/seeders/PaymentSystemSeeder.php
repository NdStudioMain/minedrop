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
        PaymentSystem::truncate();
        $systems = [
            [
                'code' => 'stars',
                'name' => 'Telegram Stars ⭐',
                'icon' => '/assets/img/stars.webp',
                'is_active' => true,
            ],
            [
                'code' => 'crypto_pay',
                'name' => 'CryptoPay (Telegram)',
                'icon' => '/assets/img/cryptobot.png',
                'is_active' => true,
            ],
            [
                'code' => 'nspk',
                'name' => 'СБП / Карта',
                'icon' => '/assets/img/sbp.webp',
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
