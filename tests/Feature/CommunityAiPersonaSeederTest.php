<?php

namespace Tests\Feature;

use App\Filament\Resources\CommunityAiPersonas\CommunityAiPersonaResource;
use App\Filament\Resources\CommunityAiPersonas\Pages\EditCommunityAiPersona;
use App\Models\CommunityAiPersona;
use App\Models\CommunityUser;
use App\Models\User;
use App\Services\Community\CommunityAiPersonaPromptBuilder;
use Database\Seeders\CommunityAiPersonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CommunityAiPersonaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_all_personas_and_their_community_accounts(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $this->assertDatabaseCount('community_ai_personas', 36);

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
        $this->assertSame(6, $sergey->prompt_version);
        $this->assertFalse($sergey->settings['web_search_enabled']);
        $this->assertSame(18, $sergey->settings['literacy_profile']['error_chance']);
        $this->assertSame(40, $sergey->settings['professional_language']['usage_chance']);
        $this->assertStringContainsString('кругорейс', $sergey->settings['professional_language']['vocabulary']);
        $this->assertStringNotContainsString('публично обозначенная AI-персона', $sergey->system_prompt);
        $this->assertStringContainsString('Характер:', $sergey->system_prompt);
        $this->assertStringContainsString('Уровень грамотности:', $sergey->system_prompt);
        $this->assertStringContainsString('Примерно в 18% сообщений', $sergey->system_prompt);
        $this->assertStringContainsString('Профессиональная лексика, которой ты владеешь', $sergey->system_prompt);
        $this->assertStringContainsString('кругорейс', $sergey->system_prompt);
        $this->assertStringContainsString('не используй длинное тире', $sergey->system_prompt);

        $this->assertSame(
            36,
            CommunityUser::query()->whereHas('aiPersona')->count(),
        );

        $newPersona = CommunityAiPersona::query()
            ->where('slug', 'viktor-fleet-carrier')
            ->firstOrFail();
        $this->assertTrue($newPersona->is_active);
        $this->assertSame('6a93eedb-0931-49a3-b085-7413d7b8dbcd', $newPersona->provider_agent_id);
        $this->assertSame(
            'https://agent.timeweb.cloud/api/v1/cloud-ai/agents/6a93eedb-0931-49a3-b085-7413d7b8dbcd/v1',
            $newPersona->provider_base_url,
        );
    }

    public function test_the_seeder_is_idempotent_and_preserves_platform_managed_settings(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $persona = CommunityAiPersona::query()
            ->where('slug', 'anna-logistician')
            ->firstOrFail();
        $settings = $persona->settings;
        $settings['custom_instructions'] = 'Не используй слово «коллеги».';
        $settings['professional_language']['vocabulary'] = 'свой термин (настройка из админки)';
        $persona->update([
            'daily_comment_limit' => 99,
            'personality_description' => 'Настройка, изменённая в админке.',
            'settings' => $settings,
        ]);

        $this->seed(CommunityAiPersonaSeeder::class);

        $this->assertDatabaseCount('community_ai_personas', 36);
        $persona->refresh();
        $this->assertSame(99, $persona->daily_comment_limit);
        $this->assertSame('Настройка, изменённая в админке.', $persona->personality_description);
        $this->assertSame('Не используй слово «коллеги».', $persona->settings['custom_instructions']);
        $this->assertStringContainsString('свой термин (настройка из админки)', $persona->settings['professional_language']['vocabulary']);
        $this->assertStringContainsString('ЭЗЗ (электронная заказ-заявка)', $persona->settings['professional_language']['vocabulary']);
    }

    public function test_platform_prompt_is_built_from_current_persona_settings(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $persona = CommunityAiPersona::query()
            ->with('communityUser')
            ->where('slug', 'mikhail-driver')
            ->firstOrFail();
        $settings = $persona->settings;
        $settings['communication_style'] = 'Пишет очень коротко и начинает сразу с практической детали.';
        $settings['custom_instructions'] = 'Иногда заканчивай реплику коротким вопросом.';
        $persona->update(['settings' => $settings]);

        $prompt = app(CommunityAiPersonaPromptBuilder::class)->build($persona->fresh('communityUser'));

        $this->assertStringContainsString('Пишет очень коротко и начинает сразу с практической детали.', $prompt);
        $this->assertStringContainsString('Иногда заканчивай реплику коротким вопросом.', $prompt);
        $this->assertStringContainsString('тахо', $prompt);
        $this->assertStringContainsString('Михаил', $prompt);
    }

    public function test_personas_receive_role_specific_professional_vocabulary(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $driver = CommunityAiPersona::query()->where('slug', 'mikhail-driver')->firstOrFail();
        $forwarder = CommunityAiPersona::query()->where('slug', 'igor-forwarder')->firstOrFail();
        $cargoOwner = CommunityAiPersona::query()->where('slug', 'olga-cargo-owner-logistician')->firstOrFail();
        $lawyer = CommunityAiPersona::query()->where('slug', 'elena-transport-lawyer')->firstOrFail();

        $this->assertStringContainsString('тахо', $driver->settings['professional_language']['vocabulary']);
        $this->assertStringContainsString('закрыть загрузку', $forwarder->settings['professional_language']['vocabulary']);
        $this->assertStringContainsString('OTIF', $cargoOwner->settings['professional_language']['vocabulary']);
        $this->assertStringContainsString('претензионный порядок', $lawyer->settings['professional_language']['vocabulary']);
        $this->assertNotSame(
            $driver->settings['professional_language']['vocabulary'],
            $forwarder->settings['professional_language']['vocabulary'],
        );

        $requiredTerms = ['ГСМ', 'ГО', 'ГП', 'ГВ', 'ЭТРН', 'ЛОП', 'ЭДО', 'СВХ', 'ЭЗЗ', 'юрлицо'];
        $this->assertTrue(CommunityAiPersona::query()->get()->every(
            function (CommunityAiPersona $persona) use ($requiredTerms): bool {
                $vocabulary = (string) data_get($persona->settings, 'professional_language.vocabulary', '');

                return collect($requiredTerms)->every(
                    fn (string $term): bool => str_contains($vocabulary, $term),
                );
            },
        ));
    }

    public function test_admin_can_open_platform_persona_settings(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);
        $persona = CommunityAiPersona::query()->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->get(CommunityAiPersonaResource::getUrl('edit', ['record' => $persona]))
            ->assertOk()
            ->assertSeeText('Характер и голос')
            ->assertSeeText('Профессиональная речь')
            ->assertSeeText('Грамотность и естественные неровности')
            ->assertSeeText('Модель выбирается в настройках самого агента Timeweb');
    }

    public function test_admin_can_save_persona_voice_settings_used_by_the_prompt(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);
        $persona = CommunityAiPersona::query()->firstOrFail();
        $settings = $persona->settings;
        $settings['communication_style'] = 'Короткие реплики без приветствий.';
        $settings['custom_instructions'] = 'Иногда уточняй цену простоя.';

        $this->actingAs(User::factory()->create());

        Livewire::test(EditCommunityAiPersona::class, ['record' => $persona->getRouteKey()])
            ->fillForm([
                'role_description' => $persona->role_description,
                'personality_description' => 'Прямой и немного ироничный практик.',
                'settings' => $settings,
                'is_active' => true,
                'requires_review' => true,
                'can_create_posts' => true,
                'can_create_comments' => true,
                'daily_post_limit' => 2,
                'daily_comment_limit' => 8,
                'max_post_tokens' => 900,
                'max_comment_tokens' => 350,
                'provider_agent_id' => $persona->provider_agent_id,
                'provider_base_url' => $persona->provider_base_url,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $persona->refresh();
        $this->assertSame('Прямой и немного ироничный практик.', $persona->personality_description);
        $this->assertSame('Короткие реплики без приветствий.', $persona->settings['communication_style']);
        $this->assertSame(8, $persona->daily_comment_limit);
        $this->assertSame(7, $persona->prompt_version);
        $this->assertStringContainsString('Иногда уточняй цену простоя.', $persona->system_prompt);
    }

    public function test_persona_usernames_look_like_public_handles_not_internal_identifiers(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $usernames = CommunityUser::query()
            ->whereHas('aiPersona')
            ->pluck('username');

        $this->assertCount(36, $usernames);
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

        $this->assertCount(36, $bios);
        $this->assertSame(36, $bios->filter()->unique()->count());
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

        $this->assertCount(36, $profiles);
        $this->assertSame(36, $profiles->pluck('description')->unique()->count());
        $this->assertGreaterThanOrEqual(15, $profiles->pluck('error_chance')->unique()->count());
        $this->assertTrue($profiles->every(
            fn (array $profile): bool => filled($profile['imperfections'])
                && $profile['error_chance'] >= 0
                && $profile['casual_chance'] >= 0
                && ($profile['error_chance'] + $profile['casual_chance']) <= 100,
        ));
    }

    public function test_personas_have_distinct_registration_dates_in_the_requested_range(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);

        $users = CommunityUser::query()
            ->whereHas('aiPersona')
            ->get();
        $registrationDates = $users->map(
            fn (CommunityUser $user): string => $user->created_at->format('Y-m-d'),
        );

        $this->assertCount(36, $registrationDates);
        $this->assertSame(36, $registrationDates->unique()->count());
        $this->assertTrue($users->every(
            fn (CommunityUser $user): bool => $user->created_at->betweenIncluded(
                '2026-07-15 00:00:00',
                '2026-09-05 23:59:59',
            ),
        ));
    }
}
