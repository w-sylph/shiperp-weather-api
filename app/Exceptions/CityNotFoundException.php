<?php

declare(strict_types=1);

namespace App\Exceptions;

final class CityNotFoundException extends WeatherException
{
    public function __construct(string $city)
    {
        parent::__construct("City '{$city}' was not found.");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'city_not_found';
    }
}
