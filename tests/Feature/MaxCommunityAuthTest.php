<?php

namespace Tests\Feature;

use App\Models\CommunityLoginChallenge;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as ClientRequest;
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
            'https://platform-api2.max.ru/messages*' => Http::response(['message' => ['body' => ['text' => 'ok']]], 200),
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

    public function test_site_opens_bot_with_one_time_challenge(): void
    {
        $response = $this->get(route('community.auth.max.start'))->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $token = $this->tokenFromDeepLink($location);
        $this->assertNotSame('', $token);
        $this->assertStringStartsWith('https://max.ru/community_bot?start=', $location);
        $this->assertStringNotContainsString('?startapp=', $location);
        $this->assertDatabaseCount('community_login_challenges', 1);
        $this->assertDatabaseCount('community_users', 0);
    }

    public function test_stale_deleted_user_session_does_not_break_challenge_creation(): void
    {
        $user = CommunityUser::factory()->create();
        $this->actingAs($user, 'community');
        $user->forceDelete();
        $this->app['auth']->forgetGuards();

        $this->get(route('community.auth.max.start'))->assertRedirect();

        $this->assertNull(CommunityLoginChallenge::query()->firstOrFail()->link_to_user_id);
    }

    public function test_bot_sends_challenge_bound_authorize_button(): void
    {
        $token = $this->startToken();

        $this->postJson(route('community.webhooks.max'), [
            'update_type' => 'bot_started',
            'user' => ['user_id' => 9911],
            'payload' => $token,
        ], [
            'X-Max-Bot-Api-Secret' => 'max_webhook-secret',
        ])->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool => data_get($request->data(), 'attachments.0.payload.buttons.0.0.type') === 'open_app'
            && data_get($request->data(), 'attachments.0.payload.buttons.0.0.web_app') === 'community_bot'
            && data_get($request->data(), 'attachments.0.payload.buttons.0.0.payload') === $token
        );
        $this->assertSame('pending', CommunityLoginChallenge::query()->firstOrFail()->status);
        $this->assertDatabaseCount('community_users', 0);
    }

    public function test_bot_sends_authorize_button_when_started_without_site_challenge(): void
    {
        $this->postJson(route('community.webhooks.max'), [
            'update_type' => 'bot_started',
            'user' => ['user_id' => 9912],
        ], [
            'X-Max-Bot-Api-Secret' => 'max_webhook-secret',
        ])->assertOk();

        Http::assertSent(fn (ClientRequest $request): bool => data_get($request->data(), 'attachments.0.payload.buttons.0.0.type') === 'open_app'
            && data_get($request->data(), 'attachments.0.payload.buttons.0.0.text') === 'Авторизоваться'
            && data_get($request->data(), 'attachments.0.payload.buttons.0.0.web_app') === 'community_bot'
            && data_get($request->data(), 'attachments.0.payload.buttons.0.0.payload') === 'community-login'
        );
        $this->assertDatabaseCount('community_users', 0);
    }

    public function test_signed_mini_app_data_approves_challenge_and_returns_to_site(): void
    {
        $token = $this->startToken();

        $result = $this->postJson(route('community.auth.max.approve'), [
            'challenge' => $token,
            'init_data' => $this->signedInitData(9922),
        ])->assertOk()->assertJsonStructure(['return_url']);

        $challenge = CommunityLoginChallenge::query()->firstOrFail();
        $this->assertSame('approved', $challenge->fresh()->status);
        $this->assertSame('max', $challenge->fresh()->communityUser?->avatar_source);
        Storage::disk('public')->assertExists($challenge->communityUser->avatar_path);

        $this->app['session']->flush();
        $this->assertGuest('community');
        $this->get($result->json('return_url'))->assertRedirect(route('community.onboarding'));
        $this->assertAuthenticated('community');
        $this->get($result->json('return_url'))->assertGone();
    }

    public function test_mini_app_without_start_parameter_can_authorize_and_return_to_site(): void
    {
        $this->get(route('community.auth.max.mini-app'))
            ->assertOk()
            ->assertSee('Подтверждаем вход')
            ->assertSee('Авторизоваться')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false);
        $result = $this->postJson(route('community.auth.max.session'), [
            'init_data' => $this->signedInitData(9933),
        ])->assertOk()->assertJsonStructure(['return_url']);

        $this->assertDatabaseCount('community_users', 1);
        $this->assertDatabaseCount('community_identities', 1);
        $this->app['session']->flush();
        $this->assertGuest('community');

        $this->get($result->json('return_url'))->assertRedirect(route('community.onboarding'));
        $this->assertAuthenticated('community');
    }

    public function test_signed_max_init_data_cannot_be_replayed(): void
    {
        $initData = $this->signedInitData(9934, 'replay-query');

        $this->postJson(route('community.auth.max.session'), [
            'init_data' => $initData,
        ])->assertOk();

        $this->postJson(route('community.auth.max.session'), [
            'init_data' => $initData,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('max');

        $this->assertDatabaseCount('community_users', 1);
        $this->assertDatabaseCount('community_identities', 1);
        $this->assertDatabaseCount('community_login_challenges', 1);
        $this->assertDatabaseCount('community_max_init_data_uses', 1);
    }

    public function test_unsigned_max_return_link_is_rejected(): void
    {
        $challenge = CommunityLoginChallenge::query()->create([
            'token_hash' => hash('sha256', 'return-test'),
            'browser_session_hash' => hash('sha256', 'browser-test'),
            'status' => 'approved',
            'expires_at' => now()->addMinutes(5),
            'approved_at' => now(),
        ]);

        $this->get(route('community.auth.max.complete', $challenge, absolute: false))->assertForbidden();
    }

    public function test_max_retries_authorization_prompt_without_creating_account(): void
    {
        Http::swap(new Factory($this->app['events']));
        Http::fake([
            'https://platform-api2.max.ru/messages*' => Http::sequence()
                ->push(['message' => 'temporary error'], 500)
                ->push(['message' => ['body' => ['text' => 'ok']]], 200),
        ]);
        $webhook = [
            'update_type' => 'bot_started',
            'user' => ['user_id' => 9944],
        ];
        $headers = ['X-Max-Bot-Api-Secret' => 'max_webhook-secret'];

        $this->postJson(route('community.webhooks.max'), $webhook, $headers)->assertServerError();
        $this->postJson(route('community.webhooks.max'), $webhook, $headers)->assertOk();
        $this->assertDatabaseCount('community_users', 0);
        $this->assertDatabaseCount('community_identities', 0);
        Http::assertSentCount(2);
    }

    public function test_max_challenge_creation_is_rate_limited(): void
    {
        foreach (range(1, 10) as $attempt) {
            $this->get(route('community.auth.max.start'))->assertRedirect();
        }

        $this->get(route('community.auth.max.start'))->assertTooManyRequests();
    }

    private function startToken(): string
    {
        $response = $this->get(route('community.auth.max.start'))->assertRedirect();

        return $this->tokenFromDeepLink((string) $response->headers->get('Location'));
    }

    private function tokenFromDeepLink(string $deepLink): string
    {
        parse_str((string) parse_url($deepLink, PHP_URL_QUERY), $query);
        $token = $query['start'] ?? null;
        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $token;
    }

    private function signedInitData(int $userId, string $queryId = 'test-query'): string
    {
        $data = [
            'auth_date' => (string) time(),
            'query_id' => $queryId,
            'user' => json_encode(['id' => $userId, 'first_name' => 'Private', 'photo_url' => 'https://images.example.test/max-avatar.png'], JSON_UNESCAPED_UNICODE),
        ];
        ksort($data);
        $check = implode("\n", array_map(fn ($key, $value) => $key.'='.$value, array_keys($data), array_values($data)));
        $secret = hash_hmac('sha256', 'max-test-token', 'WebAppData', true);
        $data['hash'] = hash_hmac('sha256', $check, $secret);

        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }
}
