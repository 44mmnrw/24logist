<?php

namespace Tests\Feature;

use App\Filament\Resources\EtrnRouletteJackpots\Pages\ListEtrnRouletteJackpots;
use App\Models\CommunityUser;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\EtrnRouletteOutcome;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EtrnRoulettePrizePlayerTest extends TestCase
{
    use RefreshDatabase;

    private array $captchaResponse;

    protected function setUp(): void
    {
        parent::setUp();
        SiteSetting::instance()->update([
            'smartcaptcha_site_key' => 'test-public-key',
            'smartcaptcha_server_key' => 'test-private-key',
        ]);
        app(SiteSettingsService::class)->clearCache();
        Http::preventStrayRequests();
        $this->captchaResponse = [
            'status' => 'ok',
            'host' => parse_url(route('etrn-roulette.attempts.store'), PHP_URL_HOST),
        ];
        Http::fake(['smartcaptcha.cloud.yandex.ru/validate' => fn () => Http::response($this->captchaResponse)]);
    }

    public function test_guest_is_sent_to_community_login_before_joining_prize_game(): void
    {
        $this->get(route('etrn-roulette.prize.join'))
            ->assertRedirect(route('community.login'))
            ->assertSessionHas('url.intended', route('etrn-roulette.prize.join'));
    }

    public function test_game_auth_provider_stores_prize_join_as_intended_destination(): void
    {
        $this->get(route('etrn-roulette.prize.auth', ['provider' => 'telegram']))
            ->assertRedirect(route('community.auth.telegram.redirect'))
            ->assertSessionHas('url.intended', route('etrn-roulette.prize.join'));
    }

    public function test_authenticated_player_is_registered_once_and_attempts_are_tracked(): void
    {
        $user = CommunityUser::factory()->create();

        $this->actingAs($user, 'community')
            ->get(route('etrn-roulette.prize.join'))
            ->assertRedirect(route('etrn-roulette'));

        $this->actingAs($user, 'community')
            ->get(route('etrn-roulette.prize.join'))
            ->assertRedirect(route('etrn-roulette'));

        $this->assertDatabaseCount('etrn_roulette_players', 1);

        $this->actingAs($user, 'community')
            ->postJson(route('etrn-roulette.attempts.store'), [
                'request_id' => (string) Str::uuid(),
                'smart_token' => 'first-token',
            ])
            ->assertOk()
            ->assertJsonPath('player_attempts', 1);

        $this->assertDatabaseHas('etrn_roulette_players', [
            'community_user_id' => $user->id,
            'attempts' => 1,
        ]);
    }

    public function test_only_server_generated_jackpots_appear_in_admin(): void
    {
        $this->withoutVite();
        $communityUser = CommunityUser::factory()->create(['display_name' => 'Победитель теста']);
        $this->actingAs($communityUser, 'community')
            ->get(route('etrn-roulette.prize.join'))
            ->assertRedirect(route('etrn-roulette'));

        $this->app->instance(EtrnRouletteOutcome::class, new EtrnRouletteOutcome(static fn (int $max): int => 500));
        $requestId = (string) Str::uuid();
        $first = $this->actingAs($communityUser, 'community')
            ->postJson(route('etrn-roulette.attempts.store'), [
                'request_id' => $requestId,
                'smart_token' => 'first-token',
                'jackpot' => false,
            ])
            ->assertOk()
            ->assertJsonPath('outcome.jackpot', true)
            ->assertJsonPath('outcome.reels.0', EtrnRouletteOutcome::LOGISTRU_SYMBOL)
            ->assertJsonPath('attempts', 1)
            ->json();

        $this->assertDatabaseHas('etrn_roulette_spins', [
            'id' => $first['spin_id'],
            'community_user_id' => $communityUser->id,
            'is_jackpot' => true,
        ]);
        $this->assertDatabaseHas('game_counters', ['key' => 'etrn-roulette-superbonus', 'attempts' => 1]);

        $this->actingAs($communityUser, 'community')
            ->postJson(route('etrn-roulette.attempts.store'), ['request_id' => $requestId])
            ->assertOk()
            ->assertJsonPath('replayed', true)
            ->assertJsonPath('attempts', 1);

        $this->actingAs($communityUser, 'community')
            ->postJson(route('etrn-roulette.attempts.store'), [
                'request_id' => (string) Str::uuid(),
                'smart_token' => 'second-token',
            ])
            ->assertOk()
            ->assertJsonPath('attempts', 2);

        $this->assertDatabaseCount('etrn_roulette_spins', 2);
        Http::assertSentCount(2);
        $admin = User::factory()->create();
        $this->actingAs($admin, 'web')
            ->get(route('filament.admin.resources.etrn-roulette-jackpots.index'))
            ->assertOk()
            ->assertSee('Победитель теста');

        Livewire::test(ListEtrnRouletteJackpots::class)
            ->assertCanSeeTableRecords([$first['spin_id']]);
    }

    public function test_client_cannot_claim_a_jackpot_or_replay_another_session_spin(): void
    {
        $this->app->instance(EtrnRouletteOutcome::class, new EtrnRouletteOutcome(static fn (int $max): int => $max));
        $requestId = (string) Str::uuid();

        $this->postJson(route('etrn-roulette.attempts.store'), [
            'request_id' => $requestId,
            'smart_token' => 'first-token',
            'jackpot' => true,
        ])->assertOk()->assertJsonPath('outcome.jackpot', false);

        $this->assertDatabaseHas('etrn_roulette_spins', ['request_id' => $requestId, 'is_jackpot' => false]);
        $this->assertDatabaseMissing('game_counters', ['key' => 'etrn-roulette-superbonus']);

        $this->actingAs(CommunityUser::factory()->create(), 'community')
            ->postJson(route('etrn-roulette.attempts.store'), ['request_id' => $requestId])
            ->assertStatus(409);
        $this->assertDatabaseCount('etrn_roulette_spins', 1);
    }

    public function test_captcha_is_required_and_rejected_tokens_cannot_create_spins(): void
    {
        $this->postJson(route('etrn-roulette.attempts.store'), ['request_id' => (string) Str::uuid()])
            ->assertUnprocessable()->assertJsonValidationErrors('smart_token');
        Http::assertNothingSent();

        $this->captchaResponse = ['status' => 'failed'];
        $this->postJson(route('etrn-roulette.attempts.store'), [
            'request_id' => (string) Str::uuid(),
            'smart_token' => 'invalid-token',
        ])->assertUnprocessable()->assertJsonValidationErrors('smart_token');
        $this->assertDatabaseCount('etrn_roulette_spins', 0);
    }
}
