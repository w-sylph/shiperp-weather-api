<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WeatherRequest;
use App\Services\Weather\WeatherService;
use Illuminate\Http\JsonResponse;

final class WeatherController extends Controller
{
    public function __construct(private readonly WeatherService $weather) {}

    public function show(WeatherRequest $request): JsonResponse
    {
        return response()->json(
            $this->weather->current($request->validated('city'))->toArray()
        );
    }

    public function cached(WeatherRequest $request): JsonResponse
    {
        return response()->json(
            $this->weather->cached($request->validated('city'))->toArray()
        );
    }
}
