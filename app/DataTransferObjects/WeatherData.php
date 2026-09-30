<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\WeatherSource;
use App\Exceptions\WeatherProviderException;
use Carbon\CarbonImmutable;

final class WeatherData
{
    public function __construct(
        public readonly string $city,
        public readonly float $temperature,
        public readonly string $description,
        public readonly CarbonImmutable $timestamp,
        public readonly WeatherSource $source = WeatherSource::External,
    ) {}

    /**
     * Rebuild from the array shape produced by toArray() (used by the cache).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            city: $data['city'],
            temperature: (float) $data['temperature'],
            description: $data['description'],
            timestamp: CarbonImmutable::parse($data['timestamp']),
            source: WeatherSource::from($data['source']),
        );
    }

    public function withSource(WeatherSource $source): self
    {
        return new self($this->city, $this->temperature, $this->description, $this->timestamp, $source);
    }

    /**
     * @return array{city: string, temperature: float, description: string, timestamp: string, source: string}
     */
    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'temperature' => $this->temperature,
            'description' => $this->description,
            'timestamp' => $this->timestamp->toIso8601String(),
            'source' => $this->source->value,
        ];
    }
}
