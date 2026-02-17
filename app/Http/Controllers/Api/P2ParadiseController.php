<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Service\P2ParadiseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class P2ParadiseController extends Controller
{
    public function __construct(
        private P2ParadiseService $p2paradiseService
    ) {}

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
     * Список платежей пользователя через НСПК / СБП
     */
    public function getPayments(Request $request): JsonResponse
    {
        $payments = $request->user()
            ->payments()
            ->whereHas('paymentSystem', fn ($q) => $q->where('code', 'nspk'))
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

    /**
     * Callback от p2paradise
     */
    public function callback(Request $request): JsonResponse
    {
        $data = $request->all();

        Log::info('P2Paradise callback received', ['type' => $data['type'] ?? null]);

        try {
            $result = $this->p2paradiseService->handleCallback($data);

            if ($result) {
                return response()->json(['status' => 'received']);
            }

            return response()->json(['status' => 'ignored'], 200);
        } catch (\Exception $e) {
            Log::error('P2Paradise callback error', [
                'message' => $e->getMessage(),
                'data' => $data,
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }
}
