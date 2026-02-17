<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Service\CryptoPayService;
use App\Service\P2ParadiseService;
use App\Service\StarPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private CryptoPayService $cryptoPayService,
        private P2ParadiseService $p2paradiseService,
        private StarPaymentService $starPaymentService
    ) {}

    /**
     * Создать платёж (универсальный эндпоинт)
     */
    public function createPayment(Request $request): JsonResponse
    {
        $request->validate([
            'method' => 'required|string|in:crypto_pay,nspk,stars',
            'amount' => 'required|numeric|min:50',
            'currency' => 'nullable|string', // Только для crypto_pay
        ]);

        $method = $request->input('method');
        $amount = (float) $request->input('amount');
        $user = $request->user();

        Log::info('Payment request', [
            'method' => $method,
            'amount' => $amount,
            'user_id' => $user->id,
        ]);

        try {
            if ($method === 'stars') {
                // Telegram Stars — минимум 50₽
                if ($amount > 500000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Максимальная сумма для Stars — 500 000 ₽',
                    ], 422);
                }

                $payment = $this->starPaymentService->createInvoice($user, $amount);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_id' => $payment->id,
                        'payment_url' => $payment->payment_url,
                        'stars_amount' => $payment->payment_data['stars_amount'] ?? 0,
                    ],
                ]);
            } elseif ($method === 'nspk') {
                // Лимит для НСПК — минимум 2000₽
                if ($amount < 2000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Минимальная сумма для СБП — 2 000 ₽',
                    ], 422);
                }

                if ($amount > 100000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Максимальная сумма для СБП — 100 000 ₽',
                    ], 422);
                }

                $payment = $this->p2paradiseService->createPayment($user, $amount, [
                    'ip' => $request->ip(),
                ]);
            } else {
                // CryptoPay — минимум 2000₽
                if ($amount < 2000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Минимальная сумма для криптовалюты — 2 000 ₽',
                    ], 422);
                }

                $currency = $request->input('currency', 'USDT');

                if ($amount > 1000000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Максимальная сумма — 1 000 000 ₽',
                    ], 422);
                }

                $cryptoAmount = $this->cryptoPayService->calculateCryptoAmount($currency, $amount);

                if (! $cryptoAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Не удалось рассчитать сумму в криптовалюте',
                    ], 422);
                }

                $payment = $this->cryptoPayService->createInvoice($user, $currency, $amount, $cryptoAmount);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_url' => $payment->payment_url,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Payment creation error', [
                'method' => $method,
                'message' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания платежа: '.$e->getMessage(),
            ], 500);
        }
    }
}
