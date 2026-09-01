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
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('google-analytics.com', false)
            ->assertDontSee('G-ABC123XYZ9', false);

        $this->assertLessThan(
            strpos($response->getContent(), '<meta charset='),
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
}
