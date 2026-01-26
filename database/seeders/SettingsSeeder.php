<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'stars_rate',
                'value' => '2.5',
                'type' => 'float',
                'group' => 'payments',
                'label' => 'Курс Stars к RUB',
                'description' => 'Сколько рублей стоит 1 Telegram Star. Например: 2.5 означает 1⭐ = 2.5₽',
            ],
            [
                'key' => 'stars_min_amount',
                'value' => '50',
                'type' => 'integer',
                'group' => 'payments',
                'label' => 'Минимальная сумма пополнения Stars (RUB)',
                'description' => 'Минимальная сумма в рублях для пополнения через Stars',
            ],
            [
                'key' => 'stars_max_amount',
                'value' => '500000',
                'type' => 'integer',
                'group' => 'payments',
                'label' => 'Максимальная сумма пополнения Stars (RUB)',
                'description' => 'Максимальная сумма в рублях для пополнения через Stars',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
