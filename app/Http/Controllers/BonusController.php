<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\Promocodes;
use App\Models\Payments;
use App\Models\PromoLog;
use App\Models\User;
use Carbon\Carbon;

class BonusController extends Controller
{
    private array $requiredChannels = [
        '@minedrop95',
        '@minedropreserve',
    ];

    public function bonusDaily(): JsonResponse
    {
        $user = Auth::user();
        $now = now()->timestamp;

        if ($user->bonus_time && ($now - $user->bonus_time) < 86400) {
            return response()->json([
                'message' => 'Бонус уже получен, попробуйте позже',
            ], 429);
        }

        $weekAgo = now()->subDays(7);

        $depositSum = Payments::where('user_id', $user->id)->where('status', 1)->where('created_at', '>=', $weekAgo)->sum('amount');

        if ($depositSum < 1000) {
            return response()->json([
                'message' => 'За последние 7 дней нужно пополнить от 1000',
            ], 403);
        }

         $bonus = random_int(20, 50);

        DB::transaction(function () use ($user, $now, $bonus) {
            $user->balance += $bonus;
            $user->bonusTime = $now;
            $user->save();
        });

        return response()->json([
            'message' => 'Ежедневный бонус получен',
            'amount'  => $bonus,
        ]);
    }

    public function activatePromo(Request $r): JsonResponse
    {
        $r->validate([
            'code' => 'required|string|max:32',
        ]);

        $user = Auth::user();
        $code = strtoupper(trim($r->code));

        $promo = Promocodes::where('name', $code)->first();

        if (!$promo) {
            return response()->json([
                'message' => 'Промокод недействителен',
            ], 422);
        }

        if($promo->activate >= $promo->activate_limit) {
            return response()->json([
                'message' => 'Промокод недействителен',
            ], 422);
        }

        if (PromoLog::where('user_id', $user->id)->where('name', $promo->name)->exists()) {
            return response()->json([
                'message' => 'Вы уже активировали этот промокод',
            ], 422);
        }

        if (!$this->checkTelegramSubscriptions($user->tg_id)) {
            return response()->json([
                'message' => 'Вы должны быть подписаны на все каналы',
            ], 403);
        }

        DB::transaction(function () use ($user, $promo) {
            $user->balance += $promo->amount;
            $user->save();

            PromoLog::create([
                'user_id'  => $user->id,
                'name' => $promo->name,
                'amount'   => $promo->amount,
            ]);
        });

        return response()->json([
            'message' => 'Промокод успешно активирован',
            'reward'  => $promo->amount,
        ]);
    }

    public function checkSubscriptions(): JsonResponse
    {
        $user = auth()->user();

        if (!$this->checkTelegramSubscriptions($user->tg_id)) {
            return response()->json([
                'ok' => false,
                'message' => 'Вы не подписаны на все каналы',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Подписка подтверждена',
        ]);
    }

    private function checkTelegramSubscriptions(int $tgId): bool
    {
        foreach ($this->requiredChannels as $channel) {
            if (!$this->isSubscribed($tgId, $channel)) {
                return false;
            }
        }

        return true;
    }

    private function isSubscribed(int $tgId, string $channel): bool
    {
        $token = env('TELEGRAM_TOKEN');

        $url = "https://api.telegram.org/bot{$token}/getChatMember";

        $response = file_get_contents(
            $url . '?' . http_build_query([
                'chat_id' => $channel,
                'user_id' => $tgId,
            ])
        );

        if (!$response) return false;

        $data = json_decode($response, true);

        if (!isset($data['ok']) || !$data['ok']) {
            return false;
        }

        return in_array(
            $data['result']['status'],
            ['member', 'administrator', 'creator'],
            true
        );
    }
}
