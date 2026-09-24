<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_google_analytics_is_loaded_only_when_enabled_with_valid_measurement_id(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('https://a.24logist.ru/', false);

        SiteSetting::instance()->update([
            'google_analytics_enabled' => true,
            'google_analytics_measurement_id' => 'G-ABC123XYZ9',
        ]);
        app(SiteSettingsService::class)->clearCache();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee("const endpoint = 'https://a.24logist.ru/'", false)
            ->assertSee("send('page_view'", false)
            ->assertSee('logistru_cookie_notice=acknowledged', false)
            ->assertSee("const clientStorageKey = 'logistru_client_id'", false)
            ->assertSee("const sessionStorageKey = 'logistru_session_id'", false)
            ->assertDontSee('logistru_analytics_client', false)
            ->assertDontSee('logistru_analytics_session', false)
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('google-analytics.com', false)
            ->assertDontSee('G-ABC123XYZ9', false);

        $this->assertGreaterThan(
            strpos($response->getContent(), 'data-cookie-consent'),
            strpos($response->getContent(), "const endpoint = 'https://a.24logist.ru/'"),
        );
        $this->assertLessThan(
            strpos($response->getContent(), '</body>'),
            strpos($response->getContent(), "const endpoint = 'https://a.24logist.ru/'"),
        );
    }

    public function test_invalid_google_analytics_measurement_id_is_not_rendered(): void
    {
        SiteSetting::instance()->update([
            'google_analytics_enabled' => true,
            'google_analytics_measurement_id' => 'UA-123456',
        ]);
        app(SiteSettingsService::class)->clearCache();

        $this->get('/')
            ->assertOk()
            ->assertDontSee('https://a.24logist.ru/', false);
    }

    public function test_tracking_is_rendered_once_across_public_pages(): void
    {
        SiteSetting::instance()->update([
            'community_enabled' => true,
            'google_analytics_enabled' => true,
            'google_analytics_measurement_id' => 'G-ABC123XYZ9',
            'yandex_metrika_enabled' => true,
            'yandex_metrika_counter_id' => '109522459',
            'route_calculator_enabled' => true,
            'route_api_base_url' => 'https://example.test/api/',
            'route_api_secret' => str_repeat('x', 32),
        ]);
        app(SiteSettingsService::class)->clearCache();

        foreach ([
            '/',
            '/community',
            '/community/rules',
            '/community/login',
            '/epd-game',
            '/etrn-roulette',
            '/route-calculator',
            '/partners/register',
            '/partners/registered',
        ] as $path) {
            $response = $this->get($path);
            $this->assertSame(200, $response->status(), $path);
            $html = $response->getContent();

            $this->assertSame(1, substr_count($html, 'data-cookie-consent'), $path);
            $this->assertSame(1, substr_count($html, 'ym(109522459, \'init\''), $path);
            $this->assertSame(1, substr_count($html, "const endpoint = 'https://a.24logist.ru/'"), $path);
        }
    }
}
