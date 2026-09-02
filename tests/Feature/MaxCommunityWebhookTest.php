<?php

namespace Tests\Feature;

use App\Models\CommunityIdentity;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\Community\MaxWebhookSubscriptionService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaxCommunityWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::instance()->update([
            'community_enabled' => true,
            'community_max_enabled' => true,
            'community_max_webhook_secret' => 'test_webhook-secret',
        ]);
        app(SiteSettingsService::class)->clearCache();
    }

    public function test_webhook_subscription_can_be_registered_with_saved_admin_settings(): void
    {
        config(['app.url' => 'https://24logist.test']);
        SiteSetting::instance()->update([
            'community_max_bot_token' => 'max-bot-token',
        ]);
        app(SiteSettingsService::class)->clearCache();
        Http::fake([
            'https://platform-api2.max.ru/subscriptions' => Http::response(['success' => true], 200),
        ]);

        $this->assertSame('Webhook MAX зарегистрирован.', app(MaxWebhookSubscriptionService::class)->register());

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://platform-api2.max.ru/subscriptions'
                && $request->hasHeader('Authorization', 'max-bot-token')
                && $request['url'] === 'https://24logist.test/community/webhooks/max'
                && $request['secret'] === 'test_webhook-secret'
                && $request['update_types'] === ['bot_started', 'bot_stopped', 'dialog_removed'];
        });
    }

    public function test_official_max_secret_header_is_required_and_bot_stop_disables_notifications(): void
    {
        $user = CommunityUser::factory()->create();
        $identity = CommunityIdentity::query()->create([
            'community_user_id' => $user->id,
            'provider' => 'max',
            'provider_user_id' => '9911',
            'bot_access' => true,
            'notifications_enabled' => true,
            'bot_status' => 'active',
        ]);

        $payload = [
            'update_type' => 'bot_stopped',
            'user' => ['user_id' => 9911],
        ];

        $this->postJson(route('community.webhooks.max'), $payload, [
            'X-Webhook-Secret' => 'test_webhook-secret',
        ])->assertForbidden();

        $this->postJson(route('community.webhooks.max'), $payload, [
            'X-Max-Bot-Api-Secret' => 'test_webhook-secret',
        ])->assertOk()->assertJson(['ok' => true]);

        $identity->refresh();
        $this->assertFalse($identity->bot_access);
        $this->assertFalse($identity->notifications_enabled);
        $this->assertSame('stopped', $identity->bot_status);
        $this->assertNotNull($identity->last_verified_at);
    }
}
