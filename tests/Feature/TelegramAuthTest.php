<?php

use App\Models\Bank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function buildTelegramInitData(array $data, string $botToken): string
{
    ksort($data);

    $dataCheckString = '';
    foreach ($data as $key => $value) {
        $dataCheckString .= $key.'='.$value."\n";
    }
    $dataCheckString = rtrim($dataCheckString, "\n");

    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $hash = hash_hmac('sha256', $dataCheckString, $secretKey);

    $data['hash'] = $hash;

    return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
}

it('authenticates telegram user with valid initData', function () {
    $botToken = 'test_bot_token:abc123';
    config()->set('services.telegram.token', $botToken);

    Bank::create([
        'name' => 'Default',
        'currency' => 'RUB',
        'default_balance' => 0,
        'totalBets' => 0,
        'totalWins' => 0,
        'rtp' => 0,
        'houseEdge' => 0,
        'maxPayoutPercent' => 0,
        'is_default' => true,
    ]);

    $userPayload = [
        'id' => 7084589048,
        'first_name' => 'XCode',
        'last_name' => '',
        'username' => 'XCode003',
        'language_code' => 'ru',
        'allows_write_to_pm' => true,
        'photo_url' => 'https://t.me/i/userpic/320/test.svg',
    ];

    $data = [
        'user' => json_encode($userPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'chat_instance' => '-3815951712515858202',
        'chat_type' => 'private',
        'auth_date' => '1769588805',
        'signature' => 'test_signature',
    ];

    $initData = buildTelegramInitData($data, $botToken);

    $response = $this->postJson('/tg/auth/login', [
        'initData' => $initData,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'redirect' => route('home'),
        ]);

    $this->assertDatabaseHas(User::class, [
        'tg_id' => $userPayload['id'],
        'username' => $userPayload['username'],
    ]);
});
