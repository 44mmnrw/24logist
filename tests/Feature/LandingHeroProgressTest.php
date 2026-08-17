<?php

namespace Tests\Feature;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use App\Support\LandingIcons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingHeroProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_bullets_render_as_delivery_progress_with_database_icons(): void
    {
        LandingSection::query()->create([
            'slug' => 'hero',
            'name' => 'Hero',
            'title' => 'ЛогистРу',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        LandingBlock::query()->create([
            'section_slug' => 'hero',
            'block_type' => 'bullet',
            'title' => 'Создавайте заявки',
            'icon' => 'icon:doc-check-circle',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $html = view('components.landing.hero', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString('hero-list hero-list--progress', $html);
        $this->assertStringContainsString('href="#icon-doc-check-circle"', $html);
        $this->assertSame('0 0 16 16', LandingIcons::viewBox('doc-check-circle'));
    }
}
