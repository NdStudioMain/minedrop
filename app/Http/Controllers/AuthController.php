<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login(Request $r) {
        // Получаем initData из JSON или query параметров
        $initData = $r->input('initData') ?? $r->get('initData');

        if (!$initData) {
            Log::error('No initData in request', [
                'request_data' => $r->all(),
                'content_type' => $r->header('Content-Type'),
            ]);
            if ($r->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'No initData'], 400);
            }
            return redirect('https://t.me/MineDropBot');
        }

        try {
            $data = $this->validateTelegramData($initData);
        } catch (\Exception $e) {
            Log::error('Telegram validation error', [
                'error' => $e->getMessage(),
                'init_data_length' => strlen($initData),
            ]);
            if ($r->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
            }
            throw $e;
        }

        $tgId = $data['user']['id'];

        $user = User::where('tg_id', $tgId)->first();

        if (!$user) {
            $user = User::create([
                'tg_id' => $tgId,
                'name' => $data['user']['first_name'],
                'username' => $data['user']['username'] ?? null,
                'avatar' => $data['user']['photo_url'] ?? null,
                'bank_id' => Bank::where('is_default', true)->first()->id,
            ]);
        }
        if ($user->avatar == null && isset($data['user']['photo_url'])) {
            $user->avatar = $data['user']['photo_url'];
            $user->save();
        }
        Auth::login($user, true);

        if ($r->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('home'),
            ]);
        }

        return redirect()->route('home');
    }
    public function loginRedirect() {
        return redirect()->route('tg.auth');
    }

    private function validateTelegramData(string $initData): array
    {
        // Парсим данные для получения значений
        parse_str($initData, $data);

        if (!isset($data['hash'])) {
            Log::error('No hash in Telegram data', [
                'parsed_keys' => array_keys($data),
                'init_data_preview' => substr($initData, 0, 100),
            ]);
            abort(403, 'Invalid Telegram data');
        }

        $hash = $data['hash'];

        // Для проверки подписи нужно использовать оригинальные URL-encoded значения
        // Разбиваем initData на пары key=value
        $pairs = [];
        foreach (explode('&', $initData) as $pair) {
            if (strpos($pair, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $pair, 2);
            // Пропускаем hash и signature
            if ($key !== 'hash' && $key !== 'signature') {
                $pairs[$key] = $value;
            }
        }

        // Сортируем по ключам
        ksort($pairs);

        // Формируем data_check_string из оригинальных URL-encoded значений
        $dataCheckString = collect($pairs)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $botToken = config('services.telegram.token');
        if (!$botToken) {
            Log::error('Telegram bot token not configured');
            abort(500, 'Telegram bot token not configured');
        }

        $secretKey = hash_hmac(
            'sha256',
            $botToken,
            'WebAppData',
            true
        );

        $calculatedHash = hash_hmac(
            'sha256',
            $dataCheckString,
            $secretKey
        );

        if (!hash_equals($calculatedHash, $hash)) {
            Log::error('Telegram validation failed', [
                'data_check_string' => $dataCheckString,
                'calculated_hash' => $calculatedHash,
                'received_hash' => $hash,
                'data_keys' => array_keys($data),
                'bot_token_length' => strlen($botToken),
                'bot_token_preview' => substr($botToken, 0, 10) . '...',
            ]);
            abort(403, 'Telegram data check failed');
        }

        if (isset($data['user']) && is_string($data['user'])) {
            $data['user'] = json_decode($data['user'], true);
        }

        return $data;
    }



    public function telegramAuth(Request $r) {
        return Inertia::render('telegramAuth');
    }
}
