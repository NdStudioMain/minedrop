<?php

namespace App\Service;

use App\Models\Payment;
use App\Models\PaymentSystem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrypturaService
{
    private string $apiUrl;

    private string $apiKey;

    private string $username;

    private ?PaymentSystem $paymentSystem = null;

    public function __construct()
    {
        $this->apiUrl = config('services.cryptura.api_url');
        $this->apiKey = config('services.cryptura.api_key');
        $this->username = config('services.cryptura.username');
    }

    /**
     * Получить платёжную систему Cryptura (НСПК)
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
     * Создать платёж через НСПК (СБП QR)
     */
    public function createPaymentWindow(User $user, float $amount): Payment
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}/api/payments", [
            'method' => 'nspk',
            'sum' => $amount,
        ]);

        if (! $response->successful()) {
            Log::error('Cryptura NSPK payment failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Ошибка создания платежа: '.$response->body());
        }

        $data = $response->json();
        $paymentDetails = $data['payment_details'] ?? [];
        $innerDetails = $paymentDetails['payment_details'] ?? [];


        $qrUrl = $innerDetails['nspk_qr'] ?? $innerDetails['number'] ?? '';

        return Payment::create([
            'user_id' => $user->id,
            'payment_system_id' => $this->getPaymentSystem()->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_url' => $qrUrl,
            'payment_id' => $data['invid'] ?? '',
            'payment_data' => [
                'invid' => $data['invid'] ?? null,
                'method' => 'nspk',
                'nspk_qr' => $qrUrl,
                'expire_at' => $paymentDetails['expire_at'] ?? null,
                'amount_usdt' => $paymentDetails['amount_usdt'] ?? null,
                'commission' => $paymentDetails['comission'] ?? null,
            ],
        ]);
    }

    /**
     * Обработать callback от Cryptura
     */
    public function handleCallback(array $data): bool
    {
        $invid = $data['invid'] ?? null;
        $status = $data['status'] ?? null;
        $signature = $data['signature'] ?? null;
        $amount = $data['amount'] ?? 0;

        if (! $invid) {
            Log::warning('Cryptura callback: invid не найден', $data);

            return false;
        }


        if (! $this->verifyCallbackSignature($amount, $invid, $signature)) {
            Log::warning('Cryptura callback: неверная подпись', [
                'invid' => $invid,
            ]);

            return false;
        }

        $payment = Payment::query()
            ->where('payment_id', $invid)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            Log::warning('Cryptura callback: платёж не найден', [
                'invid' => $invid,
            ]);

            return false;
        }


        if ($status === 'success' || $status === 'paid' || $status === 'completed') {
            return $this->completePayment($payment);
        }

        if ($status === 'failed' || $status === 'expired' || $status === 'cancelled') {
            $payment->update(['status' => 'failed']);
            Log::info('Cryptura: платёж отменён', [
                'payment_id' => $payment->id,
                'status' => $status,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Завершить платёж и зачислить средства
     */
    public function completePayment(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $user = $payment->user;


            $user->increment('balance', $payment->amount);


            $payment->update(['status' => 'completed']);

            Log::info('Cryptura: платёж успешно обработан', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'amount' => $payment->amount,
            ]);

            return true;
        });
    }

    /**
     * Проверить подпись callback
     */
    public function verifyCallbackSignature(float $amount, string $invid, string $signature): bool
    {
        $signatureBase = "{$this->username}:{$amount}:{$invid}:{$this->apiKey}";
        $expectedSignature = hash('sha256', $signatureBase);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Генерировать подпись для запроса
     */
    public function generateSignature(float $amount, string $invid): string
    {
        $signatureBase = "{$this->username}:{$amount}:{$invid}:{$this->apiKey}";

        return hash('sha256', $signatureBase);
    }
}
