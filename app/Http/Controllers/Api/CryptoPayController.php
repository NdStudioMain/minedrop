<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCryptoPayInvoiceRequest;
use App\Models\Payment;
use App\Models\PaymentSystem;
use App\Service\CryptoPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CryptoPayController extends Controller
{
    public function __construct(
        private CryptoPayService $cryptoPayService
    ) {}

    /**
     * Создать инвойс для оплаты
     */
    public function createInvoice(CreateCryptoPayInvoiceRequest $request): JsonResponse
    {
        $user = $request->user();

        $payment = $this->cryptoPayService->createInvoice(
            user: $user,
            currency: $request->validated('currency'),
            amountRub: (float) $request->validated('amount_rub'),
            cryptoAmount: (float) $request->validated('crypto_amount'),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'payment_id' => $payment->id,
                'payment_url' => $payment->payment_url,
                'amount' => $payment->amount,
                'currency' => $payment->payment_data['currency'] ?? null,
                'crypto_amount' => $payment->payment_data['crypto_amount'] ?? null,
            ],
            'message' => 'Инвойс успешно создан',
        ]);
    }

    /**
     * Вебхук для получения уведомлений об оплате
     */
    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('Crypto-Pay-Api-Signature', '');
        $body = $request->getContent();


        if (! $this->cryptoPayService->verifyWebhookSignature($body, $signature)) {
            Log::warning('CryptoPay webhook: неверная подпись', [
                'signature' => $signature,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 403);
        }

        $data = $request->all();
        $updateType = $data['update_type'] ?? null;


        if ($updateType !== 'invoice_paid') {
            return response()->json([
                'success' => true,
                'message' => 'Ignored',
            ]);
        }

        $result = $this->cryptoPayService->handleWebhook($data);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'OK' : 'Failed',
        ]);
    }

    /**
     * Получить статус платежа
     */
    public function getStatus(Request $request, int $paymentId): JsonResponse
    {
        $user = $request->user();

        $payment = Payment::query()
            ->where('id', $paymentId)
            ->where('user_id', $user->id)
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Платёж не найден',
            ], 404);
        }


        if ($payment->status === 'pending') {
            $apiStatus = $this->cryptoPayService->checkInvoiceStatus($payment);

            if ($apiStatus === 'paid' && $payment->status === 'pending') {
                $this->cryptoPayService->completePayment($payment);
                $payment->refresh();
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'payment_url' => $payment->payment_url,
                'currency' => $payment->payment_data['currency'] ?? null,
                'crypto_amount' => $payment->payment_data['crypto_amount'] ?? null,
                'created_at' => $payment->created_at,
            ],
            'message' => null,
        ]);
    }

    /**
     * Получить курсы валют
     */
    public function getExchangeRates(): JsonResponse
    {
        $rates = $this->cryptoPayService->getExchangeRates();

        return response()->json([
            'success' => true,
            'data' => $rates,
            'message' => null,
        ]);
    }

    /**
     * Получить методы оплаты и курсы криптовалют
     */
    public function getPaymentMethods(): JsonResponse
    {

        $methods = PaymentSystem::query()
            ->where('is_active', true)
            ->get()
            ->map(fn ($system) => [
                'code' => $system->code,
                'name' => $system->name,
                'icon' => $system->icon,
            ]);


        $defaultRates = [
            'TON' => 450.0,
            'USDT' => 97.0,
            'BTC' => 9500000.0,
            'ETH' => 350000.0,
            'LTC' => 9500.0,
            'BNB' => 65000.0,
            'TRX' => 12.0,
            'USDC' => 97.0,
        ];


        $rates = Cache::remember('crypto_rates_rub', 300, function () use ($defaultRates) {
            try {
                $apiRates = $this->cryptoPayService->getCryptoRatesToRub();


                return ! empty($apiRates) ? $apiRates : $defaultRates;
            } catch (\Exception $e) {
                Log::error('Ошибка получения курсов CryptoPay: '.$e->getMessage());

                return $defaultRates;
            }
        });


        $currencies = [];
        $supportedCrypto = ['TON', 'USDT', 'BTC', 'ETH', 'LTC', 'BNB', 'TRX', 'USDC'];

        foreach ($supportedCrypto as $crypto) {
            if (isset($rates[$crypto])) {
                $currencies[] = [
                    'code' => $crypto,
                    'label' => $crypto,
                    'rate_to_rub' => $rates[$crypto],
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'methods' => $methods,
                'currencies' => $currencies,
            ],
            'message' => null,
        ]);
    }

    /**
     * Получить список платежей пользователя
     */
    public function getPayments(Request $request): JsonResponse
    {
        $user = $request->user();

        $payments = Payment::query()
            ->where('user_id', $user->id)
            ->with('paymentSystem')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'payment_system' => $payment->paymentSystem?->name,
                'currency' => $payment->payment_data['currency'] ?? null,
                'crypto_amount' => $payment->payment_data['crypto_amount'] ?? null,
                'created_at' => $payment->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $payments,
            'message' => null,
        ]);
    }
}
