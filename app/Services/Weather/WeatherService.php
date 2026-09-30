<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Contracts\WeatherProvider;
use App\DataTransferObjects\WeatherData;
use App\Enums\WeatherSource;
use Illuminate\Contracts\Cache\Repository as Cache;

final class WeatherService
{
    public function __construct(
        private readonly WeatherProvider $provider,
        private readonly Cache $cache,
        private readonly int $cacheTtlSeconds = 600,
    ) {}

    /** Always fetches live data. */
    public function current(string $city): WeatherData
    {
        return $this->provider->currentByCity($city);
    }

    /** Serves from cache when possible; otherwise fetches live data and caches it. */
    public function cached(string $city): WeatherData
    {
        $key = $this->cacheKey($city);

        $hit = $this->cache->get($key);

        if (is_array($hit)) {
            return WeatherData::fromArray($hit)->withSource(WeatherSource::Cache);
        }

        $weather = $this->provider->currentByCity($city);

        // Only successful lookups reach this line, so failures are never cached.
        $this->cache->put($key, $weather->toArray(), $this->cacheTtlSeconds);

        return $weather;
    }

    private function cacheKey(string $city): string
    {
        return 'weather:current:'.sha1(mb_strtolower(trim($city)));
    }
}