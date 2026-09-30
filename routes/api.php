<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/weather/{city}', [WeatherController::class, 'show']);
    Route::get('/weather/{city}/cached', [WeatherController::class, 'cached']);
});
