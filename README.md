# Weather API (Laravel)

Two endpoints backed by the OpenWeatherMap "current weather" API.

| Endpoint | Behaviour |
|---|---|
| `GET /weather/{city}` | Always fetches live data → `"source": "external"` |
| `GET /weather/{city}/cached` | Caches for 10 min. Miss → `"external"`, hit → `"cache"` |

Temperature is returned in °C. `timestamp` is the observation time (ISO 8601, UTC).

## Run

```bash
composer install
cp .env.example .env
php artisan key:generate
# set OPENWEATHER_API_KEY in .env (free key: https://openweathermap.org/api)
php artisan serve
```

```bash
curl http://127.0.0.1:8000/weather/Philippines
curl http://127.0.0.1:8000/weather/Philippines/cached
```

## Test

```bash
php artisan test
```
Tests use `Http::fake()`, so no real API key or network is needed.

## Approach

- **Controller** is thin: validates (FormRequest) and delegates.
- **WeatherService** holds the use cases (live vs. cached).
- **WeatherProvider** interface + **OpenWeatherMapClient** isolate all
  provider-specific details (URL, params, status mapping, timeouts, retries).
  Swapping providers = new class + one binding in `AppServiceProvider`.
- **WeatherData** DTO and **WeatherSource** enum give a typed internal contract.
- **Errors** use one JSON shape: `{"error": {"code", "message"}}`
  - 422 `invalid_request`: bad city input
  - 404 `city_not_found`: unknown city
  - 502 `weather_provider_error`: timeout, 5xx, bad key, rate limit, malformed payload
  (upstream details are logged, never exposed)
- Failed lookups are never cached.
