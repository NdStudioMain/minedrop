<?php

namespace App\Service;

use App\Models\Admin;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalService
{
    private const MIN_AMOUNT = 2000;

    private const MAX_AMOUNT = 100000;

    /**
     * Создать заявку на вывод
     */
    public function createWithdrawal(
        User $user,
        float $amount,
        string $cardNumber,
        string $method = 'sbp',
        ?string $bankName = null
    ): Withdrawal {
        // Проверка баланса
        if ($user->balance < $amount) {
            throw new \Exception('Недостаточно средств на балансе');
        }

        // Проверка лимитов
        if ($amount < self::MIN_AMOUNT) {
            throw new \Exception('Минимальная сумма вывода — '.number_format(self::MIN_AMOUNT, 0, '', ' ').' ₽');
        }

        if ($amount > self::MAX_AMOUNT) {
            throw new \Exception('Максимальная сумма вывода — '.number_format(self::MAX_AMOUNT, 0, '', ' ').' ₽');
        }

        // Проверка на незавершённые заявки
        $pendingCount = Withdrawal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        if ($pendingCount > 0) {
            throw new \Exception('У вас уже есть активная заявка на вывод');
        }

        return DB::transaction(function () use ($user, $amount, $cardNumber, $method, $bankName) {
            // Списываем с баланса сразу
            $user->decrement('balance', $amount);

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => $method,
                'card_number' => $cardNumber,
                'bank_name' => $bankName,
                'status' => 'pending',
            ]);

            Log::info('Создана заявка на вывод', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Одобрить вывод (админ)
     */
    public function approve(Withdrawal $withdrawal, Admin $admin): Withdrawal
    {
        if (! $withdrawal->isPending() && ! $withdrawal->isProcessing()) {
            throw new \Exception('Заявка уже обработана');
        }

        $withdrawal->update([
            'status' => 'completed',
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        Log::info('Вывод одобрен', [
            'withdrawal_id' => $withdrawal->id,
            'admin_id' => $admin->id,
        ]);

        return $withdrawal;
    }

    /**
     * Отклонить вывод (админ) — возвращаем деньги
     */
    public function reject(Withdrawal $withdrawal, Admin $admin, ?string $comment = null): Withdrawal
    {
        if (! $withdrawal->isPending() && ! $withdrawal->isProcessing()) {
            throw new \Exception('Заявка уже обработана');
        }

        return DB::transaction(function () use ($withdrawal, $admin, $comment) {
            // Возвращаем деньги на баланс
            $withdrawal->user->increment('balance', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'rejected',
                'admin_comment' => $comment,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            Log::info('Вывод отклонён', [
                'withdrawal_id' => $withdrawal->id,
                'admin_id' => $admin->id,
                'comment' => $comment,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Взять в обработку (админ)
     */
    public function takeInProcessing(Withdrawal $withdrawal, Admin $admin): Withdrawal
    {
        if (! $withdrawal->isPending()) {
            throw new \Exception('Заявка уже в обработке или завершена');
        }

        $withdrawal->update([
            'status' => 'processing',
            'processed_by' => $admin->id,
        ]);

        return $withdrawal;
    }
}
