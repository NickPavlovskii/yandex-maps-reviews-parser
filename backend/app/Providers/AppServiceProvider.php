<?php

namespace App\Providers;

use App\Contracts\MapsParser;
use App\Services\Yandex\YandexMapsParser;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MapsParser::class, YandexMapsParser::class);
    }

    public function boot(): void
    {
        //
    }
}
