<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        if (! $r->has('initData')) {
            if ($r->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'No initData'], 400);
            }

            return redirect('https://t.me/MineDropBot');
        }
        $data = $this->validateTelegramData($r->get('initData'));

        $tgId = $data['user']['id'];

        $user = User::where('tg_id', $tgId)->first();

        if (! $user) {
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

    public function loginRedirect()
    {
        return redirect()->route('tg.auth');
    }

    private function validateTelegramData(string $initData): array
    {
        // Логируем сырой initData для отладки
        Log::debug('Raw initData received', [
            'initData' => $initData,
            'initData_length' => strlen($initData),
            'initData_hash' => md5($initData),
        ]);

        // Используем parse_str для парсинга (он автоматически декодирует значения)
        parse_str($initData, $data);

        if (! isset($data['hash'])) {
            Log::error('Hash not found in initData', [
                'initData_preview' => substr($initData, 0, 200),
                'parsed_keys' => array_keys($data),
            ]);
            abort(403, 'Invalid Telegram data');
        }

        $hash = $data['hash'];
        unset($data['hash']);

        ksort($data);

        $dataCheckString = collect($data)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $botToken = config('services.telegram.token');

        if (empty($botToken)) {
            Log::error('Telegram bot token is not configured');
            abort(500, 'Bot token not configured');
        }

        // Согласно документации Telegram: secret_key = HMAC_SHA256(bot_token, key="WebAppData")
        // В PHP hash_hmac(algo, data, key): data=bot_token, key="WebAppData"
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

        Log::info('Telegram validation attempt', [
            'init_data_length' => strlen($initData),
            'init_data_preview' => substr($initData, 0, 200).'...',
            'data_check_string_preview' => substr($dataCheckString, 0, 200).'...',
            'data_check_string_length' => strlen($dataCheckString),
            'calculated_hash' => $calculatedHash,
            'received_hash' => $hash,
            'hashes_match' => hash_equals($calculatedHash, $hash),
            'data_keys' => array_keys($data),
            'bot_token_length' => strlen($botToken),
            'bot_token_preview' => substr($botToken, 0, 15).'...'.substr($botToken, -5),
        ]);

        if (! hash_equals($calculatedHash, $hash)) {
            Log::error('Telegram validation failed - hashes do not match', [
                'calculated_hash' => $calculatedHash,
                'received_hash' => $hash,
                'data_check_string' => $dataCheckString,
                'bot_token_id' => explode(':', $botToken)[0] ?? 'unknown',
            ]);
            abort(403, 'Telegram data check failed');
        }

        if (isset($data['user']) && is_string($data['user'])) {
            $data['user'] = json_decode($data['user'], true);
        }

        return $data;
    }

    public function telegramAuth(Request $r)
    {
        return Inertia::render('telegramAuth');
    }
}
