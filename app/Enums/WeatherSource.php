<?php

declare(strict_types=1);

namespace App\Enums;

enum WeatherSource: string
{
    case External = 'external';
    case Cache = 'cache';
}
