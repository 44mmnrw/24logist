<?php

namespace Tests\Feature;

use App\Models\LandingSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingHeroSeoHeadingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_hero_renders_separate_seo_h1_and_visible_section_heading(): void
    {
        LandingSection::query()->create([
            'slug' => 'hero',
            'name' => 'Главный экран',
            'title' => 'ЛогистРу',
            'seo_h1' => 'CRM для экспедиторов: заявки, ЭТрН и контроль рейсов',
            'subtitle' => 'Удобный облачный сервис для экспедиторов',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee(
                '<h1 class="landing-hero__seo-h1">CRM для экспедиторов: заявки, ЭТрН и контроль рейсов</h1>',
                false,
            )
            ->assertSee('<h2 class="landing-hero__title">ЛогистРу</h2>', false)
            ->assertSee('<p class="landing-hero__subtitle">Удобный облачный сервис для экспедиторов</p>', false)
            ->assertDontSee('<h1>ЛогистРу</h1>', false);

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
    }

    public function test_hero_title_falls_back_to_h1_when_seo_h1_is_empty(): void
    {
        LandingSection::query()->create([
            'slug' => 'hero',
            'name' => 'Главный экран',
            'title' => 'ЛогистРу',
            'seo_h1' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('<h1 class="landing-hero__title">ЛогистРу</h1>', false);
    }
}
