<?php

namespace App\Providers;

use App\Contracts\MapsParser;
use App\Services\Yandex\YandexMapsParser;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MapsParser::class, YandexMapsParser::class);
    }

    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request) {
            return (new Limit(
                '',
                (int) config('auth.login.max_attempts'),
                (int) config('auth.login.decay_seconds'),
            ))->by(
                Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip())
            );
        });

        RateLimiter::for('login-route', function (Request $request) {
            return (new Limit(
                '',
                (int) config('auth.login.route_max_attempts'),
                (int) config('auth.login.route_decay_seconds'),
            ))->by($request->ip());
        });
    }
}
