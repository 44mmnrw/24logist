<?php

namespace Tests\Feature;

use App\Models\CommunityIdentity;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\Community\CommunitySessionTracker;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CommunityUserActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::instance()->update(['community_enabled' => true]);
        app(SiteSettingsService::class)->clearCache();
        $this->withoutVite();
    }

    public function test_authenticated_feed_visit_records_activity_and_session_metadata(): void
    {
        $user = CommunityUser::factory()->create([
            'last_seen_at' => null,
            'last_user_agent' => null,
        ]);

        $this->actingAs($user, 'community')
            ->withServerVariables([
                'REMOTE_ADDR' => '203.0.113.42',
                'HTTP_USER_AGENT' => 'Activity test browser',
            ])
            ->get(route('community.index'))
            ->assertOk();

        $user->refresh();
        $this->assertNotNull($user->last_seen_at);
        $this->assertSame('Activity test browser', $user->last_user_agent);
        $this->assertDatabaseHas('community_user_sessions', [
            'community_user_id' => $user->id,
            'ip_address' => '203.0.113.42',
            'user_agent' => 'Activity test browser',
        ]);
    }

    public function test_login_records_provider_and_later_activity_does_not_erase_it(): void
    {
        $user = CommunityUser::factory()->create();
        $identity = CommunityIdentity::query()->create([
            'community_user_id' => $user->id,
            'provider' => 'telegram',
            'provider_user_id' => '998877',
            'last_verified_at' => now(),
        ]);
        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '198.51.100.7',
            'HTTP_USER_AGENT' => 'Telegram login browser',
        ]);
        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);

        $tracker = app(CommunitySessionTracker::class);
        $tracker->login($request, $user, 'telegram');
        $user->forceFill(['last_seen_at' => now()->subMinutes(10)])->save();
        $tracker->touch($request, $user->fresh());

        $tracked = $user->sessions()->firstOrFail();
        $this->assertSame('telegram', $tracked->provider);
        $this->assertSame($identity->id, $tracked->community_identity_id);
        $this->assertSame('198.51.100.7', $tracked->ip_address);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertSame('198.51.100.7', $user->fresh()->last_login_ip);
    }
}
