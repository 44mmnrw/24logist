<?php

namespace Tests\Feature;

use App\Filament\Resources\EtrnRouletteJackpots\Pages\ListEtrnRouletteJackpots;
use App\Filament\Resources\EtrnRouletteSpins\Pages\ListEtrnRouletteSpins;
use App\Filament\Resources\EtrnRouletteSpins\Widgets\EtrnRouletteStats;
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

    public function test_player_sees_what_prize_attempt_count_means_and_can_log_out_to_game(): void
    {
        $this->withoutVite();
        SiteSetting::instance()->update(['community_enabled' => true]);
        app(SiteSettingsService::class)->clearCache();
        $user = CommunityUser::factory()->create();

        $this->actingAs($user, 'community')
            ->get(route('etrn-roulette.prize.join'))
            ->assertRedirect(route('etrn-roulette'));
        $user->etrnRoulettePlayer()->update(['attempts' => 3]);

        $this->get(route('etrn-roulette'))
            ->assertOk()
            ->assertSee('В розыгрыше')
            ->assertSee('Учтено попыток:')
            ->assertSee('data-etrn-player-attempt-count>3</strong>', false)
            ->assertSee('name="return_to" value="etrn-roulette"', false)
            ->assertSee('Выйти');

        $this->post(route('community.logout'), ['return_to' => 'etrn-roulette'])
            ->assertRedirect(route('etrn-roulette'));
        $this->assertGuest('community');
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
            'result_kind' => 'jackpot',
            'ip_address' => '127.0.0.1',
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

        $this->actingAs($admin, 'web')
            ->get(route('filament.admin.resources.etrn-roulette-spins.index'))
            ->assertOk();

        Livewire::test(ListEtrnRouletteSpins::class)
            ->assertCanSeeTableRecords([$first['spin_id']]);
    }

    public function test_admin_journal_records_guest_results_and_ip(): void
    {
        $this->withoutVite();
        $this->app->instance(EtrnRouletteOutcome::class, new EtrnRouletteOutcome(static fn (int $max): int => $max));
        $requestId = (string) Str::uuid();

        $spin = $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.15', 'HTTP_USER_AGENT' => 'Roulette test browser'])
            ->postJson(route('etrn-roulette.attempts.store'), [
                'request_id' => $requestId,
                'smart_token' => 'first-token',
            ])->assertOk()->json();

        $this->assertDatabaseHas('etrn_roulette_spins', [
            'id' => $spin['spin_id'],
            'community_user_id' => null,
            'result_kind' => 'miss',
            'ip_address' => '192.0.2.15',
            'user_agent' => 'Roulette test browser',
        ]);

        $this->app->instance(EtrnRouletteOutcome::class, new EtrnRouletteOutcome(
            static fn (int $max): int => $max === 999_999 ? 100_000 : 0,
        ));
        $match = $this->postJson(route('etrn-roulette.attempts.store'), [
            'request_id' => (string) Str::uuid(),
            'smart_token' => 'second-token',
        ])->assertOk()->json();

        $this->assertDatabaseHas('etrn_roulette_spins', [
            'id' => $match['spin_id'],
            'result_kind' => 'match',
        ]);
        $this->assertDatabaseCount('etrn_roulette_spins', 2);

        $this->actingAs(User::factory()->create(), 'web')
            ->get(route('filament.admin.resources.etrn-roulette-spins.view', ['record' => $spin['spin_id']]))
            ->assertOk()
            ->assertSee('192.0.2.15')
            ->assertSee('Roulette test browser');

        Livewire::test(ListEtrnRouletteSpins::class)
            ->assertCanSeeTableRecords([$spin['spin_id'], $match['spin_id']])
            ->filterTable('result_kind', 'match')
            ->assertCanSeeTableRecords([$match['spin_id']])
            ->assertCanNotSeeTableRecords([$spin['spin_id']]);

        Livewire::test(EtrnRouletteStats::class)
            ->assertSee('Вращений в журнале')
            ->assertSee('Совпадений')
            ->assertSee('Без совпадения');
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

    public function test_shared_next_jackpot_chance_grows_and_resets_after_superbonus(): void
    {
        $rolls = [999_999, 10_000, 10_000];
        $this->app->instance(EtrnRouletteOutcome::class, new EtrnRouletteOutcome(
            static function (int $max) use (&$rolls): int {
                return $max === 999_999 ? array_shift($rolls) : 0;
            },
        ));

        $this->getJson(route('etrn-roulette.attempts.index'))
            ->assertOk()->assertJsonPath('next_jackpot_chance_percent', 1);

        $this->postJson(route('etrn-roulette.attempts.store'), [
            'request_id' => (string) Str::uuid(),
            'smart_token' => 'first-token',
        ])->assertOk()
            ->assertJsonPath('outcome.jackpot', false)
            ->assertJsonPath('outcome.jackpot_chance_percent', 1)
            ->assertJsonPath('next_jackpot_chance_percent', 1.01);

        $this->postJson(route('etrn-roulette.attempts.store'), [
            'request_id' => (string) Str::uuid(),
            'smart_token' => 'second-token',
        ])->assertOk()
            ->assertJsonPath('outcome.jackpot', true)
            ->assertJsonPath('outcome.jackpot_chance_percent', 1.01)
            ->assertJsonPath('next_jackpot_chance_percent', 1);

        $this->postJson(route('etrn-roulette.attempts.store'), [
            'request_id' => (string) Str::uuid(),
            'smart_token' => 'third-token',
        ])->assertOk()
            ->assertJsonPath('outcome.jackpot', false)
            ->assertJsonPath('next_jackpot_chance_percent', 1.01);

        $this->assertDatabaseHas('game_counters', ['key' => 'etrn-roulette-jackpot-streak', 'attempts' => 1]);
    }
}
