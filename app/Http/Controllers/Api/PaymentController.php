<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Service\CryptoPayService;
use App\Service\CrypturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private CryptoPayService $cryptoPayService,
        private CrypturaService $crypturaService
    ) {}

    /**
     * Создать платёж (универсальный эндпоинт)
     */
    public function createPayment(Request $request): JsonResponse
    {
        $request->validate([
            'method' => 'required|string|in:crypto_pay,nspk',
            'amount' => 'required|numeric|min:100',
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
            if ($method === 'nspk') {
                // Лимит для НСПК
                if ($amount > 100000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Максимальная сумма для СБП — 100 000 ₽',
                    ], 422);
                }

                $payment = $this->crypturaService->createPaymentWindow($user, $amount);
            } else {
                // CryptoPay
                $currency = $request->input('currency', 'USDT');

                // Лимит для крипты
                if ($amount > 1000000) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Максимальная сумма — 1 000 000 ₽',
                    ], 422);
                }

                // Рассчитываем сумму в крипте
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
                'message' => 'Ошибка создания платежа: ' . $e->getMessage(),
            ], 500);
        }
    }
}
