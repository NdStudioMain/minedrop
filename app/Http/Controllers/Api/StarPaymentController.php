<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Service\StarPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StarPaymentController extends Controller
{
    public function __construct(
        private StarPaymentService $starPaymentService
    ) {}

    /**
     * Создать инвойс на оплату Stars
     */
    public function createInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:50|max:1000000',
        ]);

        try {
            $user = $request->user();
            $amount = (float) $request->input('amount');

            $payment = $this->starPaymentService->createInvoice($user, $amount);

            $starsAmount = $payment->payment_data['stars_amount'] ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_url' => $payment->payment_url,
                    'amount' => $payment->amount,
                    'stars_amount' => $starsAmount,
                ],
                'message' => sprintf('Оплатите %d ⭐ для пополнения на %.2f ₽', $starsAmount, $amount),
            ]);

        } catch (\Exception $e) {
            Log::error('Stars invoice creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook для обработки событий от Telegram
     */
    public function webhook(Request $request): JsonResponse
    {
        $update = $request->all();

        Log::info('Stars webhook received', $update);

        try {
            // Обработка pre_checkout_query
            if (isset($update['pre_checkout_query'])) {
                $this->starPaymentService->handlePreCheckoutQuery($update['pre_checkout_query']);

                return response()->json(['ok' => true]);
            }

            // Обработка successful_payment
            if (isset($update['message']['successful_payment'])) {
                $this->starPaymentService->handleSuccessfulPayment($update['message']['successful_payment']);

                return response()->json(['ok' => true]);
            }

            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('Stars webhook error', [
                'error' => $e->getMessage(),
                'update' => $update,
            ]);

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Получить информацию о Stars
     */
    public function getInfo(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->starPaymentService->getStarsInfo(),
        ]);
    }

    /**
     * Конвертировать сумму в Stars
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = (float) $request->input('amount');
        $stars = $this->starPaymentService->convertRubToStars($amount);

        return response()->json([
            'success' => true,
            'data' => [
                'amount_rub' => $amount,
                'stars' => $stars,
            ],
        ]);
    }
}
