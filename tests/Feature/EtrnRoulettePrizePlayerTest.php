<?php

namespace Tests\Feature;

use App\Models\CommunityUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtrnRoulettePrizePlayerTest extends TestCase
{
    use RefreshDatabase;

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
            ->postJson(route('etrn-roulette.attempts.store'))
            ->assertOk()
            ->assertJsonPath('player_attempts', 1);

        $this->assertDatabaseHas('etrn_roulette_players', [
            'community_user_id' => $user->id,
            'attempts' => 1,
        ]);
    }
}
