<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),

            'name' => config('app.name'),

            'quote' => [
                'message' => trim($message),
                'author' => trim($author),
            ],

            'auth' => [
                'user' => Auth::check() ? [
                    'id'       => Auth::id(),
                    'username' => Auth::user()->username,
                    'balance'  => Auth::user()->balance ?? 0,
                    'avatar'   => Auth::user()->avatar,
                    'ref_code' => Auth::user()->ref_code,
                    'ref_balance' => Auth::user()->ref_balance,
                ] : null,
            ],

            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
