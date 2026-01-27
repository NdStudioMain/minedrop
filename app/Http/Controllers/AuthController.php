<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login(Request $r): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // Проверяем, является ли запрос AJAX/JSON (по заголовкам или Content-Type)
        $isJsonRequest = $r->wantsJson()
            || $r->ajax()
            || $r->expectsJson()
            || $r->header('X-Requested-With') === 'XMLHttpRequest'
            || str_contains($r->header('Accept', ''), 'application/json');

        if (! $r->has('initData')) {
            if ($isJsonRequest) {
                return response()->json(['success' => false, 'error' => 'No initData'], 400);
            }

            return redirect('https://t.me/MineDropBot');
        }

        try {
            $data = $this->validateTelegramData($r->get('initData'));
        } catch (\Exception $e) {
            if ($isJsonRequest) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 403);
            }

            abort(403, $e->getMessage());
        }

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

        if ($isJsonRequest) {
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
        parse_str($initData, $data);

        if (! isset($data['hash'])) {
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

        if (! hash_equals($calculatedHash, $hash)) {
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
