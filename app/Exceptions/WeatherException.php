<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

abstract class WeatherException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;

    /**
     * Laravel calls render() automatically, giving every weather failure
     * the same JSON error envelope.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $this->errorCode(),
                'message' => $this->getMessage(),
            ],
        ], $this->status());
    }
}
