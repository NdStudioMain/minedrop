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
        if (!$r->has('initData')) {
            if ($r->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'No initData'], 400);
            }
            return redirect('https://t.me/MineDropBot');
        }
        $data = $this->validateTelegramData($r->get('initData'));

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
            abort(403, 'Invalid Telegram data');
        }

        $hash = $data['hash'];
        unset($data['hash']);

        // Удаляем signature, если есть (он не участвует в проверке подписи hash)
        unset($data['signature']);

        ksort($data);

        $dataCheckString = collect($data)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $botToken = config('services.telegram.token');

        // Согласно документации Telegram: secret_key = HMAC_SHA256(data="WebAppData", key=bot_token)
        // В PHP hash_hmac(algo, data, key): hash_hmac('sha256', 'WebAppData', bot_token)
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

        Log::debug('Telegram validation attempt', [
            'init_data_length' => strlen($initData),
            'data_check_string' => $dataCheckString,
            'calculated_hash' => $calculatedHash,
            'received_hash' => $hash,
            'data_keys' => array_keys($data),
            'bot_token_first_10' => substr($botToken, 0, 10),
            'bot_token_length' => strlen($botToken),
        ]);

        // Временно: пропускаем проверку в dev режиме для отладки
        $skipValidation = config('app.debug') && config('app.env') === 'local';

        if (!$skipValidation && !hash_equals($calculatedHash, $hash)) {
            Log::error('Telegram validation failed - hashes do not match');
            abort(403, 'Telegram data check failed');
        }

        Log::info('Telegram validation successful');

        if (isset($data['user']) && is_string($data['user'])) {
            $data['user'] = json_decode($data['user'], true);
        }

        return $data;
    }



    public function telegramAuth(Request $r) {
        return Inertia::render('telegramAuth');
    }
}
