<?php

namespace App\Service;

use App\Models\Payment;
use App\Models\PaymentSystem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Klev\CryptoPayApi\CryptoPay;
use Klev\CryptoPayApi\Enums\PaidBtnName;
use Klev\CryptoPayApi\Methods\CreateInvoice;
use Klev\CryptoPayApi\Methods\GetInvoices;

class CryptoPayService
{
    private CryptoPay $api;

    private PaymentSystem $paymentSystem;

    public function __construct()
    {
        $token = config('services.crypto_pay.token');
        $isTestnet = config('services.crypto_pay.is_testnet', true);

        $this->api = new CryptoPay($token, $isTestnet);
    }

    /**
     * Получить информацию о приложении
     */
    public function getMe(): array
    {
        return $this->api->getMe();
    }

    /**
     * Получить платёжную систему CryptoPay
     */
    public function getPaymentSystem(): PaymentSystem
    {
        if (! isset($this->paymentSystem)) {
            $this->paymentSystem = PaymentSystem::query()
                ->where('code', 'crypto_pay')
                ->firstOrFail();
        }

        return $this->paymentSystem;
    }

    /**
     * Создать инвойс для оплаты
     *
     * @param  string  $currency  Валюта (TON, USDT, BTC и др.)
     * @param  float  $amountRub  Сумма в рублях для зачисления
     */
    public function createInvoice(
        User $user,
        string $currency,
        float $amountRub,
        float $cryptoAmount
    ): Payment {
        $invoice = new CreateInvoice($currency, (string) $cryptoAmount);
        $invoice->description = "Пополнение баланса на {$amountRub} RUB";
        $invoice->paid_btn_name = PaidBtnName::CALLBACK;
        $invoice->paid_btn_url = config('app.url');
        $invoice->allow_comments = false;
        $invoice->allow_anonymous = false;

        $result = $this->api->createInvoice($invoice);


        $paymentUrl = $result->bot_invoice_url ?? $result->pay_url ?? '';
        $invoiceId = (string) $result->invoice_id;

        return Payment::create([
            'user_id' => $user->id,
            'payment_system_id' => $this->getPaymentSystem()->id,
            'amount' => $amountRub,
            'status' => 'pending',
            'payment_url' => $paymentUrl,
            'payment_id' => $invoiceId,
            'payment_data' => [
                'currency' => $currency,
                'crypto_amount' => $cryptoAmount,
                'invoice_id' => $invoiceId,
            ],
        ]);
    }

    /**
     * Обработать вебхук от CryptoPay
     */
    public function handleWebhook(array $data): bool
    {
        $invoiceId = (string) ($data['payload']['invoice_id'] ?? null);

        if (! $invoiceId) {
            Log::warning('CryptoPay webhook: invoice_id не найден', $data);

            return false;
        }

        $payment = Payment::query()
            ->where('payment_id', $invoiceId)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            Log::warning('CryptoPay webhook: платёж не найден', [
                'invoice_id' => $invoiceId,
            ]);

            return false;
        }

        return $this->completePayment($payment);
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

            Log::info('CryptoPay: платёж успешно обработан', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'amount' => $payment->amount,
            ]);

            return true;
        });
    }

    /**
     * Проверить статус инвойса напрямую через API
     */
    public function checkInvoiceStatus(Payment $payment): ?string
    {
        $getInvoices = new GetInvoices;
        $getInvoices->invoice_ids = $payment->payment_id;

        $invoices = $this->api->getInvoices($getInvoices);

        if (empty($invoices)) {
            return null;
        }


        $invoice = $invoices[0];

        return $invoice->status ?? null;
    }

    /**
     * Получить доступные валюты
     */
    public function getCurrencies(): array
    {
        return $this->api->getCurrencies();
    }

    /**
     * Получить курсы обмена
     */
    public function getExchangeRates(): array
    {
        return $this->api->getExchangeRates();
    }

    /**
     * Получить курсы криптовалют к рублю
     *
     * @return array<string, float> [currency => rate_to_rub]
     */
    public function getCryptoRatesToRub(): array
    {

        $coinGeckoRates = $this->getRatesFromCoinGecko();
        if (! empty($coinGeckoRates)) {
            return $coinGeckoRates;
        }


        return $this->getRatesFromCryptoPay();
    }

    /**
     * Получить курсы из CoinGecko API
     */
    private function getRatesFromCoinGecko(): array
    {

        $coinIds = [
            'TON' => 'the-open-network',
            'USDT' => 'tether',
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'LTC' => 'litecoin',
            'BNB' => 'binancecoin',
            'TRX' => 'tron',
            'USDC' => 'usd-coin',
        ];

        $ids = implode(',', array_values($coinIds));
        $url = "https:

        try {
            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                return [];
            }

            $data = $response->json();
            $result = [];

            foreach ($coinIds as $code => $geckoId) {
                if (isset($data[$geckoId]['rub'])) {
                    $result[$code] = (float) $data[$geckoId]['rub'];
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::warning('CoinGecko API error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Получить курсы из CryptoPay API (фоллбэк)
     */
    private function getRatesFromCryptoPay(): array
    {
        try {
            $rates = $this->api->getExchangeRates();
        } catch (\Exception $e) {
            Log::warning('CryptoPay API error: '.$e->getMessage());

            return [];
        }

        $result = [];
        $supportedCrypto = ['TON', 'USDT', 'BTC', 'ETH', 'LTC', 'BNB', 'TRX', 'USDC'];

        foreach ($supportedCrypto as $crypto) {
            $cryptoToUsd = null;
            foreach ($rates as $rate) {

                if ($rate['source'] === $crypto && $rate['target'] === 'USD' && $rate['is_valid']) {
                    $cryptoToUsd = (float) $rate['rate'];
                    break;
                }
            }

            $usdToRub = null;
            foreach ($rates as $rate) {
                if ($rate['source'] === 'USD' && $rate['target'] === 'RUB' && $rate['is_valid']) {
                    $usdToRub = (float) $rate['rate'];
                    break;
                }
            }

            if ($cryptoToUsd && $usdToRub) {
                $result[$crypto] = $cryptoToUsd * $usdToRub;
            }
        }

        return $result;
    }

    /**
     * Рассчитать сумму в криптовалюте
     */
    public function calculateCryptoAmount(string $currency, float $amountRub): ?float
    {
        $rates = $this->getCryptoRatesToRub();

        if (! isset($rates[$currency]) || $rates[$currency] <= 0) {
            return null;
        }


        return $amountRub / $rates[$currency];
    }

    /**
     * Верифицировать подпись вебхука
     */
    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        $token = config('services.crypto_pay.token');
        $secret = hash('sha256', $token, true);
        $checkString = $body;

        $calculatedSignature = hash_hmac('sha256', $checkString, $secret);

        return hash_equals($calculatedSignature, $signature);
    }
}
