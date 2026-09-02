<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunitySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_community_is_disabled_until_admin_setting_is_saved(): void
    {
        $settings = app(SiteSettingsService::class);

        $this->assertFalse($settings->communityEnabled());
        $this->get('/community')->assertNotFound();
    }

    public function test_database_setting_can_enable_community_and_max(): void
    {
        SiteSetting::instance()->update([
            'community_enabled' => true,
            'community_max_enabled' => true,
        ]);
        app(SiteSettingsService::class)->clearCache();

        $this->assertTrue(app(SiteSettingsService::class)->communityEnabled());
        $this->assertTrue(app(SiteSettingsService::class)->communityMaxEnabled());
        $this->get('/community/login')->assertOk();
    }

    public function test_max_is_disabled_when_whole_community_is_disabled(): void
    {
        SiteSetting::instance()->update([
            'community_enabled' => false,
            'community_max_enabled' => true,
        ]);
        app(SiteSettingsService::class)->clearCache();

        $this->assertFalse(app(SiteSettingsService::class)->communityMaxEnabled());
    }

    public function test_provider_credentials_are_encrypted_at_rest(): void
    {
        $setting = SiteSetting::instance();
        $setting->update([
            'community_telegram_client_secret' => 'telegram-secret',
            'community_telegram_bot_token' => 'telegram-token',
            'community_max_bot_token' => 'max-token',
            'community_max_webhook_secret' => 'webhook-secret',
            'community_vk_client_id' => 'vk-client-id',
            'community_vk_client_secret' => 'vk-client-secret',
            'community_vk_service_token' => 'vk-service-token',
        ]);
        app(SiteSettingsService::class)->clearCache();

        $raw = SiteSetting::query()->getQuery()->where('id', $setting->id)->first();

        $this->assertNotSame('telegram-secret', $raw->community_telegram_client_secret);
        $this->assertNotSame('max-token', $raw->community_max_bot_token);
        $this->assertNotSame('vk-client-id', $raw->community_vk_client_id);
        $this->assertNotSame('vk-client-secret', $raw->community_vk_client_secret);
        $this->assertNotSame('vk-service-token', $raw->community_vk_service_token);
        $this->assertSame('telegram-secret', app(SiteSettingsService::class)->telegramClientSecret());
        $this->assertSame('webhook-secret', app(SiteSettingsService::class)->maxWebhookSecret());
        $this->assertSame('vk-client-id', app(SiteSettingsService::class)->vkClientId());
        $this->assertSame('vk-client-secret', app(SiteSettingsService::class)->vkClientSecret());
        $this->assertSame('vk-service-token', app(SiteSettingsService::class)->vkServiceToken());
    }
}
