<?php

declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

final class WeatherProviderException extends WeatherException
{
    private function __construct(
        string $message,
        private readonly int $httpStatus = 502,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function unreachable(Throwable $previous): self
    {
        return new self('The weather service is currently unreachable.', 504, $previous);
    }

    public static function badResponse(int $status): self
    {
        return $status === 429
            ? new self('The weather service is rate limiting requests.', 503)
            : new self("The weather service returned an unexpected response ({$status}).");
    }

    public static function malformedResponse(): self
    {
        return new self('The weather service returned an invalid response.');
    }

    public static function misconfigured(): self
    {
        return new self('The weather service is not configured.', 500);
    }

    public function status(): int
    {
        return $this->httpStatus;
    }

    public function errorCode(): string
    {
        return 'weather_provider_error';
    }
}