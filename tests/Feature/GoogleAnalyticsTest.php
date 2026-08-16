<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_analytics_is_loaded_only_when_enabled_with_valid_measurement_id(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtag/js', false);

        SiteSetting::instance()->update([
            'google_analytics_enabled' => true,
            'google_analytics_measurement_id' => 'G-ABC123XYZ9',
        ]);
        app(SiteSettingsService::class)->clearCache();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123XYZ9', false)
            ->assertSee("gtag('config', \"G-ABC123XYZ9\")", false)
            ->assertSee('<script async src="https://www.googletagmanager.com', false);

        $this->assertLessThan(
            strpos($response->getContent(), '<meta charset='),
            strpos($response->getContent(), 'googletagmanager.com/gtag/js'),
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
            ->assertDontSee('googletagmanager.com/gtag/js', false);
    }
}
