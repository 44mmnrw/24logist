<?php

namespace Tests\Feature;

use App\Jobs\DeliverCommunityNotification;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\Community\CommunityNotificationService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommunityNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_opted_in_identity_creates_queued_delivery(): void
    {
        Queue::fake();
        $recipient = CommunityUser::factory()->create();
        $actor = CommunityUser::factory()->create();
        $identity = $recipient->identities()->create([
            'provider' => 'telegram', 'provider_user_id' => '101', 'bot_access' => true,
            'notifications_enabled' => true, 'bot_status' => 'active',
        ]);

        $notification = app(CommunityNotificationService::class)->create(
            $recipient, $actor, 'comment_reply', 'comment', 55,
            ['message' => 'Новый ответ', 'url' => 'https://example.test/community/p/1/topic#comment-55'],
        );

        $this->assertNotNull($notification);
        $this->assertDatabaseHas('community_notification_deliveries', ['community_identity_id' => $identity->id, 'status' => 'pending']);
        Queue::assertPushed(DeliverCommunityNotification::class);
    }

    public function test_permanent_provider_error_disables_notifications(): void
    {
        Queue::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 403)]);
        SiteSetting::instance()->update(['community_telegram_bot_token' => 'test-token']);
        app(SiteSettingsService::class)->clearCache();
        $recipient = CommunityUser::factory()->create();
        $actor = CommunityUser::factory()->create();
        $identity = $recipient->identities()->create([
            'provider' => 'telegram', 'provider_user_id' => '101', 'bot_access' => true,
            'notifications_enabled' => true, 'bot_status' => 'active',
        ]);
        $notification = app(CommunityNotificationService::class)->create(
            $recipient, $actor, 'post_reply', 'comment', 56,
            ['message' => 'Ответ', 'url' => 'https://example.test/community'],
        );
        $delivery = $notification->deliveries()->firstOrFail();

        app()->call([new DeliverCommunityNotification($delivery->id), 'handle']);

        $this->assertSame('failed', $delivery->fresh()->status);
        $this->assertFalse($identity->fresh()->notifications_enabled);
        $this->assertFalse($identity->fresh()->bot_access);
    }
}
