<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Contracts\WeatherProvider;
use App\DataTransferObjects\WeatherData;
use App\Enums\WeatherSource;
use App\Exceptions\CityNotFoundException;
use App\Exceptions\WeatherProviderException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class OpenWeatherMapClient implements WeatherProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout = 5,
    ) {}

    public function currentByCity(string $city): WeatherData
    {
        if ($this->apiKey === '') {
            Log::error('OpenWeatherMap API key is not configured.');

            throw WeatherProviderException::misconfigured();
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(
                    times: 2,
                    sleepMilliseconds: 200,
                    when: fn (Throwable $e) => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false,
                )
                ->get('weather', [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric',
                ]);
        } catch (ConnectionException $e) {
            Log::warning('OpenWeatherMap unreachable.', ['exception' => $e->getMessage()]);

            throw WeatherProviderException::unreachable($e);
        }

        if ($response->successful()) {
            return $this->toWeatherData($response->json() ?? []);
        }

        if ($response->status() === 404) {
            throw new CityNotFoundException($city);
        }

        Log::warning('OpenWeatherMap request failed.', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw WeatherProviderException::badResponse($response->status());
    }

    /**
     * Translate an OpenWeatherMap payload into our provider-agnostic DTO.
     *
     * @param  array<string, mixed>  $payload
     */
    private function toWeatherData(array $payload): WeatherData
    {
        $city = data_get($payload, 'name');
        $temperature = data_get($payload, 'main.temp');
        $description = data_get($payload, 'weather.0.description');
        $observedAt = data_get($payload, 'dt');

        if (
            ! is_string($city)
            || ! is_numeric($temperature)
            || ! is_string($description)
            || ! is_numeric($observedAt)
        ) {
            throw WeatherProviderException::malformedResponse();
        }

        return new WeatherData(
            city: $city,
            temperature: (float) $temperature,
            description: $description,
            timestamp: CarbonImmutable::createFromTimestampUTC((int) $observedAt),
            source: WeatherSource::External,
        );
    }
}
