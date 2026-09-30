<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataTransferObjects\WeatherData;
use App\Exceptions\CityNotFoundException;
use App\Exceptions\WeatherProviderException;

interface WeatherProvider
{
    /**
     * @throws CityNotFoundException
     * @throws WeatherProviderException
     */
    public function currentByCity(string $city): WeatherData;
}
