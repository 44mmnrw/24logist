<?php

namespace Tests\Feature;

use App\Models\CommunityLoginChallenge;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MaxCommunityAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Http::fake([
            'https://images.example.test/max-avatar.png' => Http::response(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='), 200, ['Content-Type' => 'image/png']),
        ]);
        SiteSetting::instance()->update([
            'community_enabled' => true,
            'community_max_enabled' => true,
            'community_max_bot_username' => 'community_bot',
            'community_max_bot_token' => 'max-test-token',
            'community_max_webhook_secret' => 'max_webhook-secret',
        ]);
        app(SiteSettingsService::class)->clearCache();
        $this->withoutVite();
    }

    public function test_external_max_bot_challenge_is_single_use(): void
    {
        $response = $this->get(route('community.auth.max.start'))->assertOk();
        preg_match('/[?&]start=([A-Za-z0-9_%\-]+)/', $response->getContent(), $matches);
        $token = rawurldecode($matches[1] ?? '');
        $this->assertNotSame('', $token);
        $challenge = CommunityLoginChallenge::query()->firstOrFail();

        $webhook = [
            'update_type' => 'bot_started',
            'user' => ['user_id' => 9911],
            'payload' => $token,
        ];

        $this->postJson(route('community.webhooks.max'), $webhook, [
            'X-Max-Bot-Api-Secret' => 'max_webhook-secret',
        ])->assertOk();

        $this->getJson(route('community.auth.max.status', $challenge))
            ->assertOk()->assertJson(['status' => 'consumed']);
        $this->assertAuthenticated('community');
        $this->assertNotNull($challenge->fresh()->consumed_at);
        $this->assertDatabaseHas('community_identities', [
            'community_user_id' => auth('community')->id(),
            'provider' => 'max',
            'provider_user_id' => '9911',
            'bot_access' => true,
            'bot_status' => 'active',
        ]);

        $this->postJson(route('community.webhooks.max'), $webhook, [
            'X-Max-Bot-Api-Secret' => 'max_webhook-secret',
        ])->assertOk();
        $this->assertDatabaseCount('community_users', 1);
        $this->assertDatabaseCount('community_identities', 1);
    }

    public function test_signed_mini_app_data_can_still_approve_challenge(): void
    {
        $response = $this->get(route('community.auth.max.start'))->assertOk();
        preg_match('/[?&]start=([A-Za-z0-9_%\-]+)/', $response->getContent(), $matches);
        $token = rawurldecode($matches[1] ?? '');

        $this->postJson(route('community.auth.max.approve'), [
            'challenge' => $token,
            'init_data' => $this->signedInitData(9922),
        ])->assertOk();

        $this->assertSame('max', CommunityLoginChallenge::query()->firstOrFail()->fresh()->communityUser?->avatar_source);
        Storage::disk('public')->assertExists(CommunityLoginChallenge::query()->firstOrFail()->communityUser->avatar_path);
    }

    public function test_max_challenge_creation_is_rate_limited(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->get(route('community.auth.max.start'))->assertOk();
        }

        $this->get(route('community.auth.max.start'))->assertTooManyRequests();
    }

    public function test_status_polling_does_not_consume_challenge_creation_limit(): void
    {
        $this->get(route('community.auth.max.start'))->assertOk();
        $challenge = CommunityLoginChallenge::query()->firstOrFail();

        foreach (range(1, 10) as $attempt) {
            $this->getJson(route('community.auth.max.status', $challenge))->assertOk();
        }

        $this->get(route('community.auth.max.start'))->assertOk();
    }

    private function signedInitData(int $userId): string
    {
        $data = [
            'auth_date' => (string) time(),
            'query_id' => 'test-query',
            'user' => json_encode(['id' => $userId, 'first_name' => 'Private', 'photo_url' => 'https://images.example.test/max-avatar.png'], JSON_UNESCAPED_UNICODE),
        ];
        ksort($data);
        $check = implode("\n", array_map(fn ($key, $value) => $key.'='.$value, array_keys($data), array_values($data)));
        $secret = hash_hmac('sha256', 'max-test-token', 'WebAppData', true);
        $data['hash'] = hash_hmac('sha256', $check, $secret);

        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }
}
