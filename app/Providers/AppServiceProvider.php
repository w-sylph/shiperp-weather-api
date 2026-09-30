<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\WeatherProvider;
use App\Services\Weather\OpenWeatherMapClient;
use App\Services\Weather\WeatherService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WeatherProvider::class, fn () => new OpenWeatherMapClient(
            apiKey: (string) config('services.openweather.key'),
            baseUrl: (string) config('services.openweather.base_url'),
            timeout: (int) config('services.openweather.timeout'),
        ));

        $this->app->singleton(WeatherService::class, fn ($app) => new WeatherService(
            provider: $app->make(WeatherProvider::class),
            cache: $app->make(Cache::class),
            cacheTtlSeconds: (int) config('services.openweather.cache_ttl'),
        ));
    }

    public function boot(): void
    {
        //
    }
}
