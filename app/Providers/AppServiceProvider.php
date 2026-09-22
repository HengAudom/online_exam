<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
    public function boot()
    {
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        Auth::provider('multi_user', function ($app, array $config) {
            return new MultiUserProvider();
        });

        // F-02: Rate limit login by IP AND by Account/Identifier
        RateLimiter::for('login', function (Request $request) {
            $rawIdentifier = $request->input('identifier') ?? $request->input('username');
            $identifier = (is_string($rawIdentifier) || is_numeric($rawIdentifier)) ? (string) $rawIdentifier : '';
            $safeIdentifier = strtolower(trim($identifier));
            return [
                Limit::perMinute(15)->by($request->ip()),
                Limit::perMinute(10)->by($safeIdentifier ?: $request->ip())->response(function () {
                    return response()->json([
                        'message' => 'Too many login attempts on this account. Please wait a moment before trying again.'
                    ], 429);
                }),
            ];
        });

        // F-03: Rate limit password reset by IP AND by Username
        RateLimiter::for('password-reset', function (Request $request) {
            $rawUsername = $request->input('username');
            $username = (is_string($rawUsername) || is_numeric($rawUsername)) ? (string) $rawUsername : '';
            $safeUser = strtolower(trim($username));
            return [
                Limit::perMinute(15)->by($request->ip()),
                Limit::perMinute(10)->by($safeUser ?: $request->ip())->response(function () {
                    return response()->json([
                        'message' => 'Too many reset requests for this account. Please wait a few moments.'
                    ], 429);
                }),
            ];
        });
    }
}
