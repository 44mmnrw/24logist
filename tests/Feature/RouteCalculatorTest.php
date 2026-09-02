<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class RouteCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'shared-route-api-secret-at-least-32-characters';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        SiteSetting::instance()->update([
            'community_enabled' => false,
            'route_calculator_enabled' => true,
            'route_api_base_url' => 'https://platform.example.test/api/internal/site/routes',
            'route_api_secret' => self::SECRET,
            'route_api_timeout' => 12,
        ]);
        app(SiteSettingsService::class)->clearCache();
    }

    public function test_page_is_available_without_exposing_api_secret(): void
    {
        $this->get(route('route-calculator.index'))
            ->assertOk()
            ->assertSee('Карта и расчёт маршрута')
            ->assertDontSee('Сообщество 24Logist')
            ->assertDontSee(self::SECRET);

        $this->assertNotSame(
            self::SECRET,
            SiteSetting::instance()->getRawOriginal('route_api_secret'),
        );
    }

    public function test_city_suggestions_are_proxied_with_server_side_secret(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response(['suggestions' => ['Москва', 'Московский']], 200),
        ]);

        $this->getJson(route('route-calculator.cities', ['query' => 'Мос']))
            ->assertOk()
            ->assertJson(['suggestions' => ['Москва', 'Московский']]);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://platform.example.test/api/internal/site/routes/city-suggest?query=%D0%9C%D0%BE%D1%81'
            && $request->hasHeader('Authorization', 'Bearer '.self::SECRET)
        );
    }

    public function test_route_calculation_is_validated_and_proxied(): void
    {
        Http::fake([
            'platform.example.test/*' => Http::response([
                'from' => ['lat' => 55.75, 'lng' => 37.61],
                'to' => ['lat' => 55.79, 'lng' => 49.12],
                'distance_km' => 820,
                'travel_time' => ['total_work_hours' => 12.5, 'total_days' => 2],
                'route_points' => [[55.75, 37.61], [55.79, 49.12]],
            ], 200),
        ]);

        $this->postJson(route('route-calculator.calculate'), [
            'from_city' => 'Москва',
            'to_city' => 'Казань',
            'routing_profile' => 'truck',
            'truck' => ['gross_weight_t' => 20],
        ])->assertOk()->assertJson(['distance_km' => 820]);

        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request['from_city'] === 'Москва'
            && $request['routing_profile'] === 'truck'
            && $request->hasHeader('Authorization', 'Bearer '.self::SECRET)
        );
    }

    public function test_disabled_calculator_is_hidden(): void
    {
        SiteSetting::instance()->update(['route_calculator_enabled' => false]);
        app(SiteSettingsService::class)->clearCache();

        $this->get(route('route-calculator.index'))->assertNotFound();
        $this->postJson(route('route-calculator.calculate'), [
            'from_city' => 'Москва',
            'to_city' => 'Казань',
        ])->assertServiceUnavailable();
    }
}
