<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\User;
use App\Service\StarPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    public function webhook()
    {
        Log::info('Telegram webhook called');

        $update = json_decode(file_get_contents('php://input'), true);

        // Обработка pre_checkout_query для Stars
        if (isset($update['pre_checkout_query'])) {
            return $this->handlePreCheckoutQuery($update['pre_checkout_query']);
        }

        // Обработка successful_payment для Stars
        if (isset($update['message']['successful_payment'])) {
            return $this->handleSuccessfulPayment($update['message']['successful_payment']);
        }

        if (! isset($update['message'])) {
            return response('ok');
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $tgUser = $message['from'];
        $text = $message['text'] ?? '';

        if (str_starts_with($text, '/start')) {
            $refCode = $this->extractRefCode($text);

            $user = $this->registerUser($tgUser, $refCode);

            $this->sendWelcome($chatId);
        }

        return response('ok');
    }

    /**
     * Обработка pre_checkout_query для Stars платежей
     */
    private function handlePreCheckoutQuery(array $preCheckoutQuery)
    {
        Log::info('Stars pre_checkout_query received', $preCheckoutQuery);

        $starService = app(StarPaymentService::class);
        $starService->handlePreCheckoutQuery($preCheckoutQuery);

        return response('ok');
    }

    /**
     * Обработка successful_payment для Stars платежей
     */
    private function handleSuccessfulPayment(array $successfulPayment)
    {
        Log::info('Stars successful_payment received', $successfulPayment);

        $starService = app(StarPaymentService::class);
        $starService->handleSuccessfulPayment($successfulPayment);

        return response('ok');
    }

    private function registerUser(array $tgUser, ?string $refCode): User
    {
        $user = User::where('tg_id', $tgUser['id'])->first();

        if ($user) {
            return $user;
        }

        return User::create([
            'tg_id' => $tgUser['id'],
            'username' => $tgUser['username'] ?? $tgUser['first_name'] ?? 'user_'.$tgUser['id'],
            'avatar' => $this->getTelegramAvatar($tgUser['id']),
            'ref_code' => $this->generateRefCode(),
            'bank_id' => Bank::where('is_default', true)->first()->id,
            'referrer_id' => $this->resolveReferrer($refCode, $tgUser['id']),
        ]);
    }

    private function resolveReferrer(?string $refCode, int $tgId): ?int
    {
        if (! $refCode) {
            return null;
        }

        $referrer = User::where('ref_code', $refCode)->first();

        if (! $referrer) {
            return null;
        }

        if ($referrer->tg_id === $tgId) {
            return null;
        }

        return $referrer->id;
    }

    private function extractRefCode(string $text): ?string
    {
        $parts = explode(' ', $text, 2);

        return $parts[1] ?? null;
    }

    private function generateRefCode(): string
    {
        do {
            $code = 'md_'.strtoupper(Str::random(6));
        } while (User::where('ref_code', $code)->exists());

        return $code;
    }

    private function getTelegramAvatar(int $tgId): ?string
    {
        $token = env('TELEGRAM_TOKEN');

        $photos = Http::get("https://api.telegram.org/bot{$token}/getUserProfilePhotos", [
            'user_id' => $tgId,
            'limit' => 1,
        ])->json();

        if (empty($photos['result']['photos'][0][0]['file_id'])) {
            return null;
        }

        $fileId = $photos['result']['photos'][0][0]['file_id'];

        $file = Http::get("https://api.telegram.org/bot{$token}/getFile", [
            'file_id' => $fileId,
        ])->json();

        if (! isset($file['result']['file_path'])) {
            return null;
        }

        return "https://api.telegram.org/file/bot{$token}/{$file['result']['file_path']}";
    }

    private function sendWelcome(int $chatId): void
    {
        $response = Http::post(
            'https://api.telegram.org/bot'.config('services.telegram.token').'/sendPhoto',
            [
                'chat_id' => $chatId,
                'photo' => 'https://ilove-youman.com/assets/img/start.jpg',
                'caption' => $this->welcomeText(),
                'parse_mode' => 'HTML',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🎮 Играть',
                                'web_app' => [
                                    'url' => route('tg.auth'),
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        // Optional: Log if the request failed
        if ($response->failed()) {
            Log::error('Failed to send Telegram welcome message', [
                'chat_id' => $chatId,
                'response' => $response->json(),
            ]);
        }
    }

    private function welcomeText(): string
    {
        return <<<'TEXT'
<b>Добро пожаловать в @MineDrop</b> —
✨ популярную мини-игру в Telegram!

🎁 Ежедневные награды
🔥 Промокоды и подарки
🚀 Быстрый геймплей
🎯 Ивенты и задания

<b>Полезные каналы для игроков:</b>
🔥 @minedrop95
🍀 @minedropreserve
TEXT;
    }
}
