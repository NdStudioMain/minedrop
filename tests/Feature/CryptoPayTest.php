<?php

use App\Models\Payment;
use App\Models\PaymentSystem;
use App\Models\User;
use App\Service\CryptoPayService;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    // Создаём платёжную систему CryptoPay
    PaymentSystem::factory()->create([
        'code' => 'crypto_pay',
        'name' => 'CryptoPay (Telegram)',
    ]);

    // Мокаем CryptoPayService по умолчанию для всех тестов
    $this->mock(CryptoPayService::class, function ($mock) {
        $mock->shouldReceive('getPaymentSystem')->andReturn(
            PaymentSystem::where('code', 'crypto_pay')->first()
        );
    })->makePartial();
});

describe('создание инвойса', function () {
    it('требует авторизации для создания инвойса', function () {
        $response = $this->postJson('/api/crypto-pay/invoice', [
            'currency' => 'TON',
            'amount_rub' => 1000,
            'crypto_amount' => 1.5,
        ]);

        $response->assertUnauthorized();
    });

    it('валидирует обязательные поля', function () {
        $user = User::factory()->create();

        actingAs($user)
            ->postJson('/api/crypto-pay/invoice', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['currency', 'amount_rub', 'crypto_amount']);
    });

    it('валидирует минимальную сумму', function () {
        $user = User::factory()->create();

        actingAs($user)
            ->postJson('/api/crypto-pay/invoice', [
                'currency' => 'TON',
                'amount_rub' => 50, // меньше минимума 100
                'crypto_amount' => 0.5,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_rub']);
    });

    it('валидирует допустимые валюты', function () {
        $user = User::factory()->create();

        actingAs($user)
            ->postJson('/api/crypto-pay/invoice', [
                'currency' => 'INVALID',
                'amount_rub' => 1000,
                'crypto_amount' => 1.5,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['currency']);
    });

    it('создаёт инвойс успешно', function () {
        $user = User::factory()->create();
        $paymentSystem = PaymentSystem::where('code', 'crypto_pay')->first();

        $this->mock(CryptoPayService::class, function ($mock) use ($user, $paymentSystem) {
            $payment = Payment::create([
                'user_id' => $user->id,
                'payment_system_id' => $paymentSystem->id,
                'amount' => 1000,
                'status' => 'pending',
                'payment_url' => 'https://t.me/CryptoBot?start=test123',
                'payment_id' => '12345678',
                'payment_data' => [
                    'currency' => 'TON',
                    'crypto_amount' => 1.5,
                ],
            ]);

            $mock->shouldReceive('createInvoice')
                ->once()
                ->andReturn($payment);
        });

        actingAs($user)
            ->postJson('/api/crypto-pay/invoice', [
                'currency' => 'TON',
                'amount_rub' => 1000,
                'crypto_amount' => 1.5,
            ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payment_id',
                    'payment_url',
                    'amount',
                    'currency',
                    'crypto_amount',
                ],
                'message',
            ]);
    });
});

describe('статус платежа', function () {
    it('возвращает статус платежа пользователя', function () {
        $user = User::factory()->create();
        $paymentSystem = PaymentSystem::where('code', 'crypto_pay')->first();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'payment_system_id' => $paymentSystem->id,
            'status' => 'pending',
        ]);

        $this->mock(CryptoPayService::class, function ($mock) {
            $mock->shouldReceive('checkInvoiceStatus')
                ->once()
                ->andReturn('pending');
        });

        actingAs($user)
            ->getJson("/api/crypto-pay/status/{$payment->id}")
            ->assertSuccessful()
            ->assertJsonPath('data.status', 'pending');
    });

    it('не показывает платежи других пользователей', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $paymentSystem = PaymentSystem::where('code', 'crypto_pay')->first();

        $payment = Payment::factory()->create([
            'user_id' => $user1->id,
            'payment_system_id' => $paymentSystem->id,
        ]);

        actingAs($user2)
            ->getJson("/api/crypto-pay/status/{$payment->id}")
            ->assertNotFound();
    });
});

describe('вебхук', function () {
    it('отклоняет запрос с неверной подписью', function () {
        $this->mock(CryptoPayService::class, function ($mock) {
            $mock->shouldReceive('verifyWebhookSignature')
                ->once()
                ->andReturn(false);
        });

        $this->postJson('/api/crypto-pay/webhook', [
            'update_type' => 'invoice_paid',
            'payload' => ['invoice_id' => '12345'],
        ], ['Crypto-Pay-Api-Signature' => 'invalid'])
            ->assertForbidden();
    });

    it('обрабатывает событие оплаты', function () {
        $user = User::factory()->create(['balance' => 0]);
        $paymentSystem = PaymentSystem::where('code', 'crypto_pay')->first();

        Payment::factory()->create([
            'user_id' => $user->id,
            'payment_system_id' => $paymentSystem->id,
            'payment_id' => '12345678',
            'amount' => 1000,
            'status' => 'pending',
        ]);

        $this->mock(CryptoPayService::class, function ($mock) {
            $mock->shouldReceive('verifyWebhookSignature')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('handleWebhook')
                ->once()
                ->andReturn(true);
        });

        $this->postJson('/api/crypto-pay/webhook', [
            'update_type' => 'invoice_paid',
            'payload' => ['invoice_id' => '12345678'],
        ], ['Crypto-Pay-Api-Signature' => 'valid'])
            ->assertSuccessful()
            ->assertJsonPath('success', true);
    });

    it('игнорирует другие типы событий', function () {
        $this->mock(CryptoPayService::class, function ($mock) {
            $mock->shouldReceive('verifyWebhookSignature')
                ->once()
                ->andReturn(true);
        });

        $this->postJson('/api/crypto-pay/webhook', [
            'update_type' => 'other_event',
            'payload' => [],
        ], ['Crypto-Pay-Api-Signature' => 'valid'])
            ->assertSuccessful()
            ->assertJsonPath('message', 'Ignored');
    });
});

describe('зачисление средств', function () {
    it('зачисляет баланс при успешной оплате', function () {
        $user = User::factory()->create(['balance' => 100]);
        $paymentSystem = PaymentSystem::where('code', 'crypto_pay')->first();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'payment_system_id' => $paymentSystem->id,
            'payment_id' => '12345678',
            'amount' => 1000,
            'status' => 'pending',
        ]);

        // Тестируем напрямую метод completePayment через мок
        $mockService = Mockery::mock(CryptoPayService::class)->makePartial();
        $this->app->instance(CryptoPayService::class, $mockService);

        $result = $mockService->completePayment($payment);

        expect($result)->toBeTrue();

        $user->refresh();
        $payment->refresh();

        expect($user->balance)->toBe('1100.00');
        expect($payment->status)->toBe('completed');
    });
});

describe('список платежей', function () {
    it('возвращает платежи текущего пользователя', function () {
        $user = User::factory()->create();
        $paymentSystem = PaymentSystem::where('code', 'crypto_pay')->first();

        Payment::factory()->count(3)->create([
            'user_id' => $user->id,
            'payment_system_id' => $paymentSystem->id,
        ]);

        // Платёж другого пользователя
        Payment::factory()->create([
            'payment_system_id' => $paymentSystem->id,
        ]);

        actingAs($user)
            ->getJson('/api/crypto-pay/payments')
            ->assertSuccessful()
            ->assertJsonCount(3, 'data');
    });
});

describe('тест для конкретного пользователя', function () {
    it('зачисляет баланс пользователю с tg_id 6424102837', function () {
        // Получаем или создаём пользователя
        $user = User::updateOrCreate(
            ['tg_id' => 6424102837],
            [
                'name' => 'ND',
                'username' => 'nd_code',
                'balance' => 33521910.80,
                'ref_balance' => 0,
            ]
        );

        $paymentSystem = PaymentSystem::where('code', 'crypto_pay')->first();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'payment_system_id' => $paymentSystem->id,
            'amount' => 5000,
            'status' => 'pending',
        ]);

        $initialBalance = (float) $user->balance;

        // Тестируем напрямую метод completePayment через мок
        $mockService = Mockery::mock(CryptoPayService::class)->makePartial();
        $this->app->instance(CryptoPayService::class, $mockService);

        $result = $mockService->completePayment($payment);

        expect($result)->toBeTrue();

        $user->refresh();
        $payment->refresh();

        expect((float) $user->balance)->toBe($initialBalance + 5000);
        expect($payment->status)->toBe('completed');
    });
});
