<?php

namespace Database\Factories;

use App\Models\PaymentSystem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payment_system_id' => PaymentSystem::factory(),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'status' => 'pending',
            'payment_url' => 'https://t.me/CryptoBot?start='.fake()->uuid(),
            'payment_id' => (string) fake()->randomNumber(8),
            'payment_data' => [
                'currency' => 'TON',
                'crypto_amount' => fake()->randomFloat(8, 0.1, 10),
                'invoice' => [
                    'invoice_id' => fake()->randomNumber(8),
                    'status' => 'pending',
                ],
            ],
        ];
    }

    /**
     * Indicate that the payment is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }
}
