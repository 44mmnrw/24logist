<?php

namespace Tests\Feature;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use App\Support\LandingPlatform;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPlatformContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_nested_card_texts_can_be_loaded_and_synchronized(): void
    {
        $card = LandingBlock::query()->create([
            'section_slug' => 'platform',
            'block_type' => 'card',
            'title' => 'Карточка',
            'is_active' => true,
        ]);

        LandingPlatform::syncContent($card, [
            'platform_note_text' => '<strong>5 минут</strong> на оформление',
            'platform_note_icon' => 'clock',
            'platform_list_items' => [
                ['title' => 'ЭТрН', 'icon' => 'check-circle'],
                ['title' => 'Экспедиторская расписка', 'icon' => 'check-circle'],
            ],
            'platform_pills' => [
                ['title' => 'Серверы в РФ'],
                ['title' => 'Шифрование'],
            ],
            'platform_roles' => [
                ['title' => 'Экспедитор', 'subtitle' => 'при подписании расписки'],
            ],
        ]);

        $state = LandingPlatform::contentFormState($card);

        $this->assertSame('<strong>5 минут</strong> на оформление', $state['platform_note_text']);
        $this->assertSame(['ЭТрН', 'Экспедиторская расписка'], array_column($state['platform_list_items'], 'title'));
        $this->assertSame(['Серверы в РФ', 'Шифрование'], array_column($state['platform_pills'], 'title'));
        $this->assertSame('Экспедитор', $state['platform_roles'][0]['title']);
        $this->assertSame('при подписании расписки', $state['platform_roles'][0]['subtitle']);

        LandingPlatform::syncContent($card, [
            'platform_note_text' => '',
            'platform_list_items' => [['title' => 'Заказ-заявка']],
            'platform_pills' => [],
            'platform_roles' => [],
        ]);

        $this->assertDatabaseMissing('landing_blocks', [
            'parent_id' => $card->id,
            'block_type' => 'note',
        ]);
        $this->assertDatabaseHas('landing_blocks', [
            'parent_id' => $card->id,
            'block_type' => 'list_item',
            'title' => 'Заказ-заявка',
        ]);
        $this->assertSame(1, $card->children()->count());
    }

    public function test_card_icon_colors_are_saved_and_rendered(): void
    {
        LandingSection::query()->create([
            'slug' => 'platform',
            'name' => 'Platform',
            'title' => 'Platform',
            'is_active' => true,
        ]);

        $card = LandingBlock::query()->create([
            'section_slug' => 'platform',
            'block_type' => 'card',
            'title' => 'Requests',
            'icon' => 'tabler:clipboard-list',
            'extra' => LandingPlatform::iconStyleExtra([
                'extra' => [
                    'icon_background_color' => '#112233',
                    'icon_color' => '#abcdef',
                ],
            ]),
            'is_active' => true,
        ]);

        $this->assertSame([
            'background' => '#112233',
            'icon' => '#abcdef',
        ], LandingPlatform::iconColors($card));

        app(LandingPageService::class)->clearCache();

        $html = view('components.landing.platform', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString(
            'style="--platform-icon-background: #112233; --platform-icon-color: #abcdef;"',
            $html,
        );
    }

    public function test_invalid_icon_colors_fall_back_to_the_design_defaults(): void
    {
        $extra = LandingPlatform::iconStyleExtra([
            'extra' => [
                'icon_background_color' => 'red; display:none',
                'icon_color' => '',
            ],
        ]);

        $this->assertSame(LandingPlatform::DEFAULT_ICON_BACKGROUND_COLOR, $extra['icon_background_color']);
        $this->assertSame(LandingPlatform::DEFAULT_ICON_COLOR, $extra['icon_color']);
    }
}
