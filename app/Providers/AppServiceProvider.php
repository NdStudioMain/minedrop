<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Глобальный API лимит - 60 запросов в минуту
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Бонусы - очень строгий лимит (5 запросов в минуту)
        RateLimiter::for('bonus', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Слишком много запросов. Подождите минуту.',
                    ], 429);
                });
        });

        // Игры - умеренный лимит (30 запросов в минуту на юзера)
        RateLimiter::for('games', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Слишком много запросов. Играйте медленнее.',
                    ], 429);
                });
        });

        // Платежи - строгий лимит (5 запросов в минуту)
        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Слишком много запросов на платежи.',
                    ], 429);
                });
        });

        // Выводы - очень строгий лимит (3 запроса в минуту)
        RateLimiter::for('withdrawals', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Слишком много запросов на вывод.',
                    ], 429);
                });
        });

        // Авторизация - защита от брутфорса (10 запросов в минуту по IP)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Слишком много попыток авторизации.',
                    ], 429);
                });
        });

        // Slots/Wallet (minedrop) - 2 запроса в секунду
        RateLimiter::for('wallet', function (Request $request) {
            return Limit::perSecond(2)->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rate limit exceeded.',
                    ], 429);
                });
        });
    }
}
