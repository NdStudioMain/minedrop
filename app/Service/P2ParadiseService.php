<?php

namespace App\Service;

use App\Models\Payment;
use App\Models\PaymentSystem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class P2ParadiseService
{
    private string $apiUrl;

    private int $merchantId;

    private string $merchantSecretKey;

    private ?PaymentSystem $paymentSystem = null;

    public function __construct()
    {
        $this->apiUrl = config('services.p2paradise.api_url');
        $this->merchantId = (int) config('services.p2paradise.merchant_id');
        $this->merchantSecretKey = config('services.p2paradise.merchant_secret_key');
    }

    /**
     * Получить платёжную систему (НСПК / СБП)
     */
    public function getPaymentSystem(): PaymentSystem
    {
        if (! $this->paymentSystem) {
            $this->paymentSystem = PaymentSystem::query()
                ->where('code', 'nspk')
                ->firstOrFail();
        }

        return $this->paymentSystem;
    }

    /**
     * Создать платёж через p2paradise (СБП / карта)
     *
     * @param  array<string, string>  $options  payment_method: sbp|sbp-card|card|..., ip: client IP
     */
    public function createPayment(User $user, float $amount, array $options = []): Payment
    {
        $amountKopeks = (int) round($amount * 100);
        $merchantCustomerId = (string) $user->id;
        $ip = $options['ip'] ?? request()->ip() ?? '0.0.0.0';
        $paymentMethod = $options['payment_method'] ?? 'sbp';
        $returnUrl = $options['return_url'] ?? null;

        $body = [
            'amount' => $amountKopeks,
            'payment_method' => $paymentMethod,
            'merchant_customer_id' => $merchantCustomerId,
            'ip' => $ip,
            'description' => 'Пополнение баланса #'.$user->id,
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ];

        if ($returnUrl) {
            $body['return_url'] = $returnUrl;
        }

        $response = Http::withHeaders([
            'merchant-id' => (string) $this->merchantId,
            'merchant-secret-key' => $this->merchantSecretKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/api/payments", $body);

        if (! $response->successful()) {
            Log::error('P2Paradise payment failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Ошибка создания платежа: '.$response->body());
        }

        $data = $response->json();
        $uuid = $data['uuid'] ?? '';
        $redirectUrl = $data['redirect_url'] ?? '';
        $incomeAmountKopeks = $data['income_amount'] ?? $amountKopeks;

        $payment = Payment::create([
            'user_id' => $user->id,
            'payment_system_id' => $this->getPaymentSystem()->id,
            'amount' => $incomeAmountKopeks / 100,
            'status' => 'pending',
            'payment_url' => $redirectUrl,
            'payment_id' => $uuid,
            'payment_data' => [
                'uuid' => $uuid,
                'payment_method' => $paymentMethod,
                'amount_kopeks' => $amountKopeks,
                'income_amount_kopeks' => $incomeAmountKopeks,
                'expires_at' => $data['expires_at'] ?? null,
                'created_at' => $data['created_at'] ?? null,
                'payment_method_bank' => $data['payment_method']['bank'] ?? null,
                'payment_method_phone' => $data['payment_method']['phone'] ?? null,
                'payment_method_name' => $data['payment_method']['name'] ?? null,
            ],
        ]);

        return $payment;
    }

    /**
     * Обработать callback от p2paradise
     */
    public function handleCallback(array $data): bool
    {
        $type = $data['type'] ?? null;
        $object = $data['object'] ?? [];
        $secretKey = $data['secret_key'] ?? '';

        if (! $this->verifyCallbackSecret($secretKey)) {
            Log::warning('P2Paradise callback: неверный secret_key');

            return false;
        }

        if ($type === 'payment.success') {
            return $this->handlePaymentSuccess($object);
        }

        if ($type === 'payment.expired') {
            return $this->handlePaymentExpired($object);
        }

        if (in_array($type, ['payout.success', 'payout.error'])) {
            Log::info('P2Paradise payout notification', ['type' => $type, 'object' => $object]);

            return true;
        }

        return false;
    }

    private function handlePaymentSuccess(array $object): bool
    {
        $uuid = $object['uuid'] ?? null;
        $incomeAmountKopeks = $object['income_amount'] ?? 0;

        if (! $uuid) {
            Log::warning('P2Paradise callback: uuid не найден', $object);

            return false;
        }

        $payment = Payment::query()
            ->where('payment_id', $uuid)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            Log::warning('P2Paradise callback: платёж не найден', ['uuid' => $uuid]);

            return false;
        }

        return $this->completePayment($payment, $incomeAmountKopeks / 100);
    }

    private function handlePaymentExpired(array $object): bool
    {
        $uuid = $object['uuid'] ?? null;

        if (! $uuid) {
            return false;
        }

        $payment = Payment::query()
            ->where('payment_id', $uuid)
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $payment->update(['status' => 'expired']);
            Log::info('P2Paradise: платёж истёк', ['payment_id' => $payment->id]);
        }

        return true;
    }

    /**
     * Завершить платёж и зачислить средства
     */
    public function completePayment(Payment $payment, ?float $amount = null): bool
    {
        return DB::transaction(function () use ($payment, $amount) {
            $user = $payment->user;
            $creditAmount = $amount ?? $payment->amount;

            $user->increment('balance', $creditAmount);

            $payment->update(['status' => 'completed']);

            Log::info('P2Paradise: платёж успешно обработан', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'amount' => $creditAmount,
            ]);

            return true;
        });
    }

    private function verifyCallbackSecret(string $secretKey): bool
    {
        return hash_equals($this->merchantSecretKey, $secretKey);
    }

    /**
     * Получить статус платежа в p2paradise
     */
    public function getPaymentStatus(string $uuid): ?array
    {
        $response = Http::withHeaders([
            'merchant-id' => (string) $this->merchantId,
            'merchant-secret-key' => $this->merchantSecretKey,
        ])->get("{$this->apiUrl}/api/payments/{$uuid}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }
}
