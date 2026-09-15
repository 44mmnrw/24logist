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
        $this->assertSame(
            'Свои машины, свои водители и своя головная боль :) Считаю простои, ремонт и что в итоге осталось от ставки.',
            $sergey->communityUser->bio,
        );
        $this->assertSame('GPT-5.4 Mini', $sergey->model);
        $this->assertSame('efbb486a-ff4a-44f7-832e-b97670296143', $sergey->provider_agent_id);
        $this->assertStringContainsString('сухую иронию', $sergey->personality_description);
        $this->assertTrue($sergey->requires_review);
        $this->assertSame(1, $sergey->daily_comment_limit);
        $this->assertSame(3, $sergey->prompt_version);
        $this->assertFalse($sergey->settings['web_search_enabled']);
        $this->assertSame(12, $sergey->settings['literacy_profile']['error_chance']);
        $this->assertStringNotContainsString('публично обозначенная AI-персона', $sergey->system_prompt);
        $this->assertStringContainsString('Характер:', $sergey->system_prompt);
        $this->assertStringContainsString('Уровень грамотности:', $sergey->system_prompt);

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

    public function test_personas_have_individual_human_sounding_profile_bios(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $bios = CommunityUser::query()
            ->whereHas('aiPersona')
            ->pluck('bio');

        $this->assertCount(11, $bios);
        $this->assertSame(11, $bios->filter()->unique()->count());
        $this->assertTrue($bios->every(
            fn (?string $bio): bool => filled($bio)
                && mb_strlen($bio) <= 1000
                && ! str_contains($bio, 'AI-персона'),
        ));
    }

    public function test_every_persona_has_a_distinct_literacy_profile(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $profiles = CommunityAiPersona::query()
            ->get()
            ->map(fn (CommunityAiPersona $persona): array => $persona->settings['literacy_profile']);

        $this->assertCount(11, $profiles);
        $this->assertSame(11, $profiles->pluck('description')->unique()->count());
        $this->assertSame(11, $profiles->pluck('error_chance')->unique()->count());
        $this->assertTrue($profiles->every(
            fn (array $profile): bool => filled($profile['imperfections'])
                && $profile['error_chance'] >= 0
                && $profile['casual_chance'] >= 0
                && ($profile['error_chance'] + $profile['casual_chance']) <= 100,
        ));
    }
}
