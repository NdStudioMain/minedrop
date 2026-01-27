<?php

namespace App\Service;

use App\Models\Payment;
use App\Models\PaymentSystem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StarPaymentService
{
    private string $botToken;

    private string $apiUrl = 'https:

    public function __construct()
    {
        $this->botToken = config('services.telegram.token');
    }

    /**
     * Создать инвойс на оплату в Stars
     */
    public function createInvoice(User $user, float $amountRub): Payment
    {
        $starsRate = Setting::getStarsRate();
        $starsAmount = (int) ceil($amountRub / $starsRate);


        if ($starsAmount < 1) {
            $starsAmount = 1;
        }

        $paymentSystem = PaymentSystem::where('code', 'stars')->first();

        if (! $paymentSystem) {
            throw new \Exception('Платёжная система Stars не найдена');
        }


        $payment = Payment::create([
            'user_id' => $user->id,
            'payment_system_id' => $paymentSystem->id,
            'amount' => $amountRub,
            'status' => 'pending',
            'payment_data' => [
                'stars_amount' => $starsAmount,
                'stars_rate' => $starsRate,
                'telegram_id' => $user->telegram_id,
            ],
        ]);


        $invoiceLink = $this->createInvoiceLink($payment, $starsAmount);

        $payment->update([
            'payment_url' => $invoiceLink,
            'payment_id' => 'stars_'.$payment->id,
        ]);

        return $payment;
    }

    /**
     * Создать ссылку на инвойс через Telegram API
     */
    private function createInvoiceLink(Payment $payment, int $starsAmount): string
    {
        $response = Http::post($this->apiUrl.$this->botToken.'/createInvoiceLink', [
            'title' => 'Пополнение баланса',
            'description' => sprintf('Пополнение на %.2f ₽ (%d ⭐)', $payment->amount, $starsAmount),
            'payload' => json_encode([
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'amount' => $payment->amount,
            ]),
            'currency' => 'XTR',
            'provider_token' => '',
            'prices' => [
                [
                    'label' => 'Пополнение баланса',
                    'amount' => $starsAmount,
                ],
            ],
        ]);

        if (! $response->successful()) {
            Log::error('Stars createInvoiceLink failed', [
                'response' => $response->json(),
                'payment_id' => $payment->id,
            ]);
            throw new \Exception('Не удалось создать инвойс: '.($response->json()['description'] ?? 'Unknown error'));
        }

        $data = $response->json();

        if (! $data['ok']) {
            throw new \Exception('Telegram API error: '.($data['description'] ?? 'Unknown error'));
        }

        return $data['result'];
    }

    /**
     * Обработать pre_checkout_query
     */
    public function handlePreCheckoutQuery(array $preCheckoutQuery): bool
    {
        $queryId = $preCheckoutQuery['id'];

        try {
            $payload = json_decode($preCheckoutQuery['invoice_payload'], true);
            $paymentId = $payload['payment_id'] ?? null;

            if (! $paymentId) {
                $this->answerPreCheckoutQuery($queryId, false, 'Некорректный платёж');

                return false;
            }

            $payment = Payment::find($paymentId);

            if (! $payment) {
                $this->answerPreCheckoutQuery($queryId, false, 'Платёж не найден');

                return false;
            }

            if ($payment->status !== 'pending') {
                $this->answerPreCheckoutQuery($queryId, false, 'Платёж уже обработан');

                return false;
            }


            $this->answerPreCheckoutQuery($queryId, true);

            return true;

        } catch (\Exception $e) {
            Log::error('Stars pre_checkout_query error', [
                'error' => $e->getMessage(),
                'query' => $preCheckoutQuery,
            ]);
            $this->answerPreCheckoutQuery($queryId, false, 'Внутренняя ошибка');

            return false;
        }
    }

    /**
     * Ответить на pre_checkout_query
     */
    private function answerPreCheckoutQuery(string $queryId, bool $ok, ?string $errorMessage = null): void
    {
        $params = [
            'pre_checkout_query_id' => $queryId,
            'ok' => $ok,
        ];

        if (! $ok && $errorMessage) {
            $params['error_message'] = $errorMessage;
        }

        Http::post($this->apiUrl.$this->botToken.'/answerPreCheckoutQuery', $params);
    }

    /**
     * Обработать successful_payment
     */
    public function handleSuccessfulPayment(array $successfulPayment): bool
    {
        try {
            $payload = json_decode($successfulPayment['invoice_payload'], true);
            $paymentId = $payload['payment_id'] ?? null;

            if (! $paymentId) {
                Log::error('Stars successful_payment: no payment_id in payload', $successfulPayment);

                return false;
            }

            $payment = Payment::find($paymentId);

            if (! $payment) {
                Log::error('Stars successful_payment: payment not found', ['payment_id' => $paymentId]);

                return false;
            }


            if ($payment->status === 'completed') {
                Log::info('Stars payment already completed', ['payment_id' => $paymentId]);

                return true;
            }


            $payment->update([
                'status' => 'completed',
                'payment_data' => array_merge($payment->payment_data ?? [], [
                    'telegram_payment_charge_id' => $successfulPayment['telegram_payment_charge_id'] ?? null,
                    'provider_payment_charge_id' => $successfulPayment['provider_payment_charge_id'] ?? null,
                    'completed_at' => now()->toISOString(),
                ]),
            ]);


            $user = $payment->user;
            $user->increment('balance', $payment->amount);

            Log::info('Stars payment completed', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'amount' => $payment->amount,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Stars successful_payment error', [
                'error' => $e->getMessage(),
                'payment' => $successfulPayment,
            ]);

            return false;
        }
    }

    /**
     * Возврат платежа
     */
    public function refundPayment(Payment $payment): bool
    {
        if ($payment->status !== 'completed') {
            throw new \Exception('Можно вернуть только завершённый платёж');
        }

        $telegramChargeId = $payment->payment_data['telegram_payment_charge_id'] ?? null;

        if (! $telegramChargeId) {
            throw new \Exception('Telegram charge ID не найден');
        }

        $response = Http::post($this->apiUrl.$this->botToken.'/refundStarPayment', [
            'user_id' => $payment->payment_data['telegram_id'],
            'telegram_payment_charge_id' => $telegramChargeId,
        ]);

        if (! $response->successful() || ! $response->json()['ok']) {
            Log::error('Stars refund failed', [
                'response' => $response->json(),
                'payment_id' => $payment->id,
            ]);

            return false;
        }


        $payment->user->decrement('balance', $payment->amount);

        $payment->update([
            'status' => 'refunded',
            'payment_data' => array_merge($payment->payment_data ?? [], [
                'refunded_at' => now()->toISOString(),
            ]),
        ]);

        return true;
    }

    /**
     * Получить информацию о курсе Stars
     */
    public function getStarsInfo(): array
    {
        $rate = Setting::getStarsRate();

        return [
            'rate' => $rate,
            'min_stars' => 1,
            'currency' => 'XTR',
            'description' => sprintf('1 ⭐ = %.2f ₽', $rate),
        ];
    }

    /**
     * Конвертировать RUB в Stars
     */
    public function convertRubToStars(float $amountRub): int
    {
        $rate = Setting::getStarsRate();

        return (int) ceil($amountRub / $rate);
    }
}
