<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class WeatherApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openweather.key' => 'test-key']);
        Cache::flush();
    }

    /** @return array<string, mixed> */
    private function testPayload(): array
    {
        return [
            'name' => 'Philippines',
            'main' => ['temp' => 15.2],
            'weather' => [['description' => 'light rain']],
            'dt' => 1700000000,
        ];
    }

    public function test_it_returns_live_weather_from_the_external_api(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response($this->testPayload())]);

        $this->getJson('/weather/Philippines')
            ->assertOk()
            ->assertExactJson([
                'city' => 'Philippines',
                'temperature' => 15.2,
                'description' => 'light rain',
                'timestamp' => '2023-11-14T22:13:20+00:00',
                'source' => 'external',
            ]);

        Http::assertSent(fn (Request $request) => $request['q'] === 'Philippines'
            && $request['appid'] === 'test-key'
            && $request['units'] === 'metric');
    }

    public function test_cached_endpoint_serves_second_request_from_cache(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response($this->testPayload())]);

        $this->getJson('/weather/Philippines/cached')
            ->assertOk()
            ->assertJsonPath('source', 'external');

        $this->getJson('/weather/Philippines/cached')
            ->assertOk()
            ->assertJsonPath('source', 'cache')
            ->assertJsonPath('city', 'Philippines');

        Http::assertSentCount(1);
    }

    public function test_live_endpoint_never_reads_from_cache(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response($this->testPayload())]);

        $this->getJson('/weather/Philippines/cached');
        $this->getJson('/weather/Philippines')->assertJsonPath('source', 'external');

        Http::assertSentCount(2);
    }

    public function test_unknown_city_returns_404(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response(['cod' => '404'], 404)]);

        $this->getJson('/weather/Atlantis')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'city_not_found');
    }

    public function test_upstream_server_error_returns_502(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response([], 500)]);

        $this->getJson('/weather/Philippines')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'weather_provider_error');
    }

    public function test_invalid_api_key_is_reported_as_502_without_leaking_details(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response(['message' => 'Invalid API key'], 401)]);

        $this->getJson('/weather/Philippines')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'weather_provider_error')
            ->assertJsonMissingPath('error.details');
    }

    public function test_malformed_upstream_payload_returns_502(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::response(['unexpected' => true])]);

        $this->getJson('/weather/Philippines')
            ->assertStatus(502)
            ->assertJsonPath('error.code', 'weather_provider_error');
    }

    public function test_connection_failure_returns_504(): void
    {
        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $this->getJson('/weather/Philippines')
            ->assertStatus(504)
            ->assertJsonPath('error.code', 'weather_provider_error');
    }

    public function test_failed_lookups_are_not_cached(): void
    {
        Http::fake(['api.openweathermap.org/*' => Http::sequence()
            ->push(['cod' => '404'], 404)
            ->push($this->testPayload()),
        ]);

        $this->getJson('/weather/Philippines/cached')->assertNotFound();

        $this->getJson('/weather/Philippines/cached')
            ->assertOk()
            ->assertJsonPath('source', 'external');
    }

    public function test_invalid_city_is_rejected_before_calling_the_api(): void
    {
        Http::fake();

        $this->getJson('/weather/12345')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'invalid_request')
            ->assertJsonStructure(['error' => ['code', 'message', 'details' => ['city']]]);

        Http::assertNothingSent();
    }
}
