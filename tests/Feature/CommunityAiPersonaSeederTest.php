<?php

namespace Tests\Feature;

use App\Models\CommunityAiPersona;
use App\Models\CommunityUser;
use Database\Seeders\CommunityAiPersonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityAiPersonaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_personas_and_their_community_accounts(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $this->assertDatabaseCount('community_ai_personas', 11);

        $sergey = CommunityAiPersona::query()
            ->with('communityUser')
            ->where('slug', 'sergey-fleet-owner')
            ->firstOrFail();

        $this->assertSame('Сергей Ковалёв', $sergey->communityUser->display_name);
        $this->assertSame('vtoraya_smena76', $sergey->communityUser->username);
        $this->assertSame('carrier', $sergey->communityUser->transport_role);
        $this->assertSame('GPT-5.4 Mini', $sergey->model);
        $this->assertSame('efbb486a-ff4a-44f7-832e-b97670296143', $sergey->provider_agent_id);
        $this->assertStringContainsString('сухую иронию', $sergey->personality_description);
        $this->assertTrue($sergey->requires_review);
        $this->assertSame(1, $sergey->daily_comment_limit);
        $this->assertFalse($sergey->settings['web_search_enabled']);
        $this->assertStringContainsString('публично обозначенная AI-персона', $sergey->system_prompt);
        $this->assertStringContainsString('Характер:', $sergey->system_prompt);

        $this->assertSame(
            11,
            CommunityUser::query()->whereHas('aiPersona')->count(),
        );
    }

    public function test_the_seeder_is_idempotent_and_restores_seeded_settings(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        CommunityAiPersona::query()
            ->where('slug', 'anna-logistician')
            ->update(['daily_comment_limit' => 99]);

        $this->seed(CommunityAiPersonaSeeder::class);

        $this->assertDatabaseCount('community_ai_personas', 11);
        $this->assertSame(
            1,
            CommunityAiPersona::query()->where('slug', 'anna-logistician')->value('daily_comment_limit'),
        );
    }

    public function test_persona_usernames_look_like_public_handles_not_internal_identifiers(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $usernames = CommunityUser::query()
            ->whereHas('aiPersona')
            ->pluck('username');

        $this->assertCount(11, $usernames);
        $this->assertTrue($usernames->every(
            fn (string $username): bool => ! str_starts_with($username, 'ai_'),
        ));
        $this->assertSame($usernames->count(), $usernames->unique()->count());
    }

    public function test_persona_display_names_have_a_natural_mix_of_name_formats(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $displayNames = CommunityUser::query()
            ->whereHas('aiPersona')
            ->pluck('display_name');

        $this->assertContains('Сергей Ковалёв', $displayNames);
        $this->assertContains('Елена Викторовна', $displayNames);
        $this->assertContains('Михаил', $displayNames);
    }
}
