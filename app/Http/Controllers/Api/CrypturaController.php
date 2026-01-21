<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Service\CrypturaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CrypturaController extends Controller
{
    public function __construct(
        private CrypturaService $crypturaService
    ) {}

    /**
     * Создать платёж через окно оплаты
     */
    public function createPayment(Request $request): JsonResponse
    {
        Log::info('Cryptura createPayment request', [
            'amount' => $request->input('amount'),
            'user_id' => $request->user()?->id,
        ]);

        $request->validate([
            'amount' => 'required|numeric|min:100|max:100000',
        ]);

        try {
            $payment = $this->crypturaService->createPaymentWindow(
                user: $request->user(),
                amount: (float) $request->input('amount'),
            );

            Log::info('Cryptura payment created', [
                'payment_id' => $payment->id,
                'payment_url' => $payment->payment_url,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_url' => $payment->payment_url,
                    'invid' => $payment->payment_id,
                    'expire_at' => $payment->payment_data['expire_at'] ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Cryptura createPayment error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Callback от Cryptura
     */
    public function callback(Request $request): JsonResponse
    {
        $data = $request->all();

        Log::info('Cryptura callback received', $data);

        try {
            $result = $this->crypturaService->handleCallback($data);

            if ($result) {
                return response()->json(['status' => 'received']);
            }

            return response()->json(['status' => 'ignored'], 200);
        } catch (\Exception $e) {
            Log::error('Cryptura callback error', [
                'message' => $e->getMessage(),
                'data' => $data,
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Получить статус платежа
     */
    public function getStatus(Request $request, int $paymentId): JsonResponse
    {
        $payment = $request->user()
            ->payments()
            ->where('id', $paymentId)
            ->first();

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Платёж не найден',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'created_at' => $payment->created_at,
            ],
        ]);
    }

    /**
     * Список платежей пользователя через НСПК
     */
    public function getPayments(Request $request): JsonResponse
    {
        $payments = $request->user()
            ->payments()
            ->whereHas('paymentSystem', function ($query) {
                $query->where('code', 'nspk');
            })
            ->latest()
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'status' => $p->status,
                'created_at' => $p->created_at,
            ]),
        ]);
    }
}
