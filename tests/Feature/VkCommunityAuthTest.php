<?php

namespace Tests\Feature;

use App\Models\CommunityIdentity;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VkCommunityAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        SiteSetting::instance()->update([
            'community_enabled' => true,
            'community_vk_enabled' => true,
            'community_vk_client_id' => '12345678',
            'community_vk_client_secret' => 'protected-key',
            'community_vk_service_token' => 'service-token',
            'community_vk_redirect_uri' => 'https://example.test/community/auth/vk/callback',
        ]);
        app(SiteSettingsService::class)->clearCache();
        $this->withoutVite();
    }

    public function test_vk_id_pkce_flow_authenticates_without_storing_tokens(): void
    {
        $redirect = $this->get(route('community.auth.vk.redirect'))->assertRedirect();
        $location = (string) $redirect->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertSame('https', parse_url($location, PHP_URL_SCHEME));
        $this->assertSame('id.vk.ru', parse_url($location, PHP_URL_HOST));
        $this->assertSame('/authorize', parse_url($location, PHP_URL_PATH));
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('s256', $query['code_challenge_method']);
        $this->assertNotSame('', $query['code_challenge']);

        $flow = session('community.vk');
        $this->assertIsArray($flow);
        Http::fake([
            'https://id.vk.ru/oauth2/auth' => Http::response([
                'access_token' => 'must-not-be-stored',
                'refresh_token' => 'must-not-be-stored-either',
                'state' => $flow['state'],
                'user_id' => 778899,
            ]),
            'https://id.vk.ru/oauth2/user_info' => Http::response([
                'user' => ['user_id' => 778899, 'avatar' => 'https://images.example.test/vk-avatar.png'],
            ]),
            'https://images.example.test/vk-avatar.png' => Http::response(
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $this->get(route('community.auth.vk.callback', [
            'code' => 'one-time-code',
            'device_id' => 'vk-device-id',
            'state' => $flow['state'],
        ]))->assertRedirect(route('community.onboarding'));

        $this->assertAuthenticated('community');
        $identity = CommunityIdentity::query()->firstOrFail();
        $this->assertSame('vk', $identity->provider);
        $this->assertSame('778899', $identity->provider_user_id);
        $this->assertFalse($identity->bot_access);
        $this->assertStringNotContainsString('must-not-be-stored', $identity->toJson());
        $this->assertSame('vk', auth('community')->user()->fresh()->avatar_source);
        Storage::disk('public')->assertExists(auth('community')->user()->fresh()->avatar_path);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://id.vk.ru/oauth2/auth'
            && $request['code'] === 'one-time-code'
            && $request['client_id'] === '12345678'
            && $request['service_token'] === 'service-token'
            && $request['code_verifier'] !== ''
            && ! isset($request['client_secret']));
    }

    public function test_vk_login_requires_service_token_for_confidential_backend_flow(): void
    {
        SiteSetting::instance()->update(['community_vk_service_token' => null]);
        app(SiteSettingsService::class)->clearCache();

        $this->get(route('community.auth.vk.redirect'))
            ->assertServiceUnavailable();
    }

    public function test_vk_id_can_be_linked_only_from_authenticated_profile(): void
    {
        $user = CommunityUser::factory()->create();
        $this->actingAs($user, 'community')->get(route('community.auth.vk.redirect'))->assertRedirect();
        $flow = session('community.vk');
        Http::fake([
            'https://id.vk.ru/oauth2/auth' => Http::response([
                'state' => $flow['state'],
                'user_id' => 991122,
            ]),
        ]);

        $this->get(route('community.auth.vk.callback', [
            'code' => 'link-code',
            'device_id' => 'link-device',
            'state' => $flow['state'],
        ]))->assertRedirect(route('community.index'));

        $this->assertDatabaseCount('community_users', 1);
        $this->assertDatabaseHas('community_identities', [
            'community_user_id' => $user->id,
            'provider' => 'vk',
            'provider_user_id' => '991122',
        ]);
    }

    public function test_vk_callback_rejects_state_mismatch_before_token_exchange(): void
    {
        Http::fake();
        $this->get(route('community.auth.vk.redirect'))->assertRedirect();

        $this->get(route('community.auth.vk.callback', [
            'code' => 'code',
            'device_id' => 'device',
            'state' => 'wrong-state',
        ]))->assertSessionHasErrors('vk');

        Http::assertNothingSent();
    }
}
