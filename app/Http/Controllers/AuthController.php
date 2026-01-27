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

        parse_str($initData, $data);

        if (!isset($data['hash'])) {
            Log::error('No hash in Telegram data', [
                'parsed_keys' => array_keys($data),
                'init_data_preview' => substr($initData, 0, 100),
            ]);
            abort(403, 'Invalid Telegram data');
        }

        $hash = $data['hash'];



        // Извлекаем пары key=value из оригинальной строки
        $pairs = [];
        foreach (explode('&', $initData) as $pair) {
            if (strpos($pair, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $pair, 2);
            // URL декодируем только ключ, значение оставляем как есть (URL-encoded)
            $key = urldecode($key);

            // Пропускаем hash и signature
            if ($key !== 'hash' && $key !== 'signature') {
                $pairs[$key] = $value; // Значение остается URL-encoded
            }
        }

        // Сортируем по ключам (алфавитно)
        ksort($pairs);

        // Формируем data_check_string: key=value\nkey=value
        $dataCheckString = collect($pairs)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $botToken = config('services.telegram.token');
        if (!$botToken) {
            Log::error('Telegram bot token not configured');
            abort(500, 'Telegram bot token not configured');
        }

        // Согласно документации Telegram: secret_key = HMAC_SHA256("WebAppData", bot_token)
        // В PHP: hash_hmac(algo, data, key) = HMAC_SHA256(data, key)
        $secretKey = hash_hmac(
            'sha256',
            'WebAppData',
            $botToken,
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
                'data_check_string_hex' => bin2hex($dataCheckString),
                'calculated_hash' => $calculatedHash,
                'received_hash' => $hash,
                'pairs_keys' => array_keys($pairs),
                'pairs_values_preview' => array_map(fn($v) => substr($v, 0, 50), $pairs),
                'bot_token_length' => strlen($botToken),
                'bot_token_preview' => substr($botToken, 0, 10) . '...',
                'init_data_preview' => substr($initData, 0, 200),
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
