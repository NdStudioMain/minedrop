<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function login(Request $r) {
        if (!$r->has('initData')) {
            return redirect('https://t.me/MineDropBot');
        }

        $data = $this->validateTelegramData($r->get('initData'));

        $tgId = $data['user']['id'];

        $user = User::where('tg_id', $tgId)->first();

        if (!$user) {
            abort(403, 'User not registered');
        }

        Auth::login($user, true);
        return redirect()->route('home');
    }

    public function telegramAuth(): Response
    {
        return Inertia::render('telegramAuth');
    }

    public function telegramLogin(Request $r): \Illuminate\Http\JsonResponse
    {
        if (!$r->has('initData')) {
            return response()->json(['error' => 'initData is required'], 400);
        }

        $data = $this->validateTelegramData($r->input('initData'));

        $tgId = $data['user']['id'];

        $user = User::where('tg_id', $tgId)->first();

        if (!$user) {
            return response()->json(['error' => 'User not registered'], 403);
        }

        Auth::login($user, true);

        return response()->json(['success' => true]);
    }

    private function validateTelegramData(string $initData): array
    {
        parse_str($initData, $data);

        if (!isset($data['hash'])) {
            abort(403, 'Invalid Telegram data');
        }

        $hash = $data['hash'];
        unset($data['hash']);

        ksort($data);

        $dataCheckString = collect($data)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac(
            'sha256',
            env('TELEGRAM_TOKEN'),
            'WebAppData',
            true
        );

        $calculatedHash = hash_hmac(
            'sha256',
            $dataCheckString,
            $secretKey
        );

        if (!hash_equals($calculatedHash, $hash)) {
            abort(403, 'Telegram data check failed');
        }

        if (isset($data['user']) && is_string($data['user'])) {
            $data['user'] = json_decode($data['user'], true);
        }

        return $data;
    }
}
