<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Service\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(
        private WithdrawalService $withdrawalService
    ) {}

    /**
     * Создать заявку на вывод
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:2000|max:100000',
            'card_number' => 'required|string|min:10|max:20',
            'method' => 'nullable|string|in:sbp,card',
            'bank_name' => 'nullable|string|max:100',
        ]);

        try {
            $withdrawal = $this->withdrawalService->createWithdrawal(
                user: $request->user(),
                amount: (float) $request->input('amount'),
                cardNumber: $request->input('card_number'),
                method: $request->input('method', 'sbp'),
                bankName: $request->input('bank_name'),
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $withdrawal->id,
                    'amount' => $withdrawal->amount,
                    'status' => $withdrawal->status,
                    'created_at' => $withdrawal->created_at->toIso8601String(),
                ],
                'message' => 'Заявка на вывод создана',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Список заявок пользователя
     */
    public function index(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $withdrawals->map(fn (Withdrawal $w) => [
                'id' => $w->id,
                'amount' => $w->amount,
                'method' => $w->method,
                'status' => $w->status,
                'card_number' => $this->maskCardNumber($w->card_number),
                'admin_comment' => $w->admin_comment,
                'created_at' => $w->created_at->toIso8601String(),
                'processed_at' => $w->processed_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Статус заявки
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $withdrawal = Withdrawal::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $withdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Заявка не найдена',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status,
                'admin_comment' => $withdrawal->admin_comment,
                'processed_at' => $withdrawal->processed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Отменить заявку (только pending)
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $withdrawal = Withdrawal::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $withdrawal) {
            return response()->json([
                'success' => false,
                'message' => 'Заявка не найдена или уже обрабатывается',
            ], 404);
        }

        // Возвращаем деньги
        $withdrawal->user->increment('balance', $withdrawal->amount);
        $withdrawal->update(['status' => 'rejected', 'admin_comment' => 'Отменено пользователем']);

        return response()->json([
            'success' => true,
            'message' => 'Заявка отменена, средства возвращены на баланс',
        ]);
    }

    private function maskCardNumber(string $number): string
    {
        $length = strlen($number);
        if ($length <= 4) {
            return $number;
        }

        return str_repeat('*', $length - 4).substr($number, -4);
    }
}
