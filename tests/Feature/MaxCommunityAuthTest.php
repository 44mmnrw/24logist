<?php

namespace Tests\Feature;

use App\Models\CommunityLoginChallenge;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaxCommunityAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SiteSetting::instance()->update([
            'community_enabled' => true,
            'community_max_enabled' => true,
            'community_max_bot_username' => 'community_bot',
            'community_max_bot_token' => 'max-test-token',
        ]);
        app(SiteSettingsService::class)->clearCache();
        $this->withoutVite();
    }

    public function test_external_max_challenge_is_single_use(): void
    {
        $response = $this->get(route('community.auth.max.start'))->assertOk();
        preg_match('/startapp=([A-Za-z0-9_%\-]+)/', $response->getContent(), $matches);
        $token = rawurldecode($matches[1] ?? '');
        $this->assertNotSame('', $token);
        $challenge = CommunityLoginChallenge::query()->firstOrFail();

        $this->postJson(route('community.auth.max.approve'), [
            'challenge' => $token,
            'init_data' => $this->signedInitData(9911),
        ])->assertOk();

        $this->getJson(route('community.auth.max.status', $challenge))
            ->assertOk()->assertJson(['status' => 'consumed']);
        $this->assertAuthenticated('community');
        $this->assertNotNull($challenge->fresh()->consumed_at);

        $this->postJson(route('community.auth.max.approve'), [
            'challenge' => $token,
            'init_data' => $this->signedInitData(9911),
        ])->assertUnprocessable();
    }

    private function signedInitData(int $userId): string
    {
        $data = [
            'auth_date' => (string) time(),
            'query_id' => 'test-query',
            'user' => json_encode(['id' => $userId, 'first_name' => 'Private'], JSON_UNESCAPED_UNICODE),
        ];
        ksort($data);
        $check = implode("\n", array_map(fn ($key, $value) => $key.'='.$value, array_keys($data), array_values($data)));
        $secret = hash_hmac('sha256', 'max-test-token', 'WebAppData', true);
        $data['hash'] = hash_hmac('sha256', $check, $secret);

        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }
}
