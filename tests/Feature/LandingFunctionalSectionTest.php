<?php

namespace Tests\Feature;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use Database\Seeders\FunctionalSectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingFunctionalSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_functional_section_matches_the_new_card_and_quote_structure(): void
    {
        $html = view('components.landing.why', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString('<h2>Функционал</h2>', $html);
        $this->assertSame(6, substr_count($html, 'class="why-card"'));
        $this->assertSame(6, substr_count($html, 'class="why-card__tag"'));
        $this->assertStringContainsString('3–5 минут на заявку', $html);
        $this->assertStringContainsString('Проверка контрагентов', $html);
        $this->assertStringContainsString('class="why-quote"', $html);
        $this->assertStringContainsString('Станислав Аристов', $html);
        $this->assertStringContainsString('images/functional/security.svg', $html);
    }

    public function test_deploy_seeder_restores_database_backed_quote_and_icons(): void
    {
        LandingBlock::query()->where('section_slug', 'why')->delete();
        LandingSection::query()->where('slug', 'why')->delete();

        $this->seed(FunctionalSectionSeeder::class);

        $section = LandingSection::query()->where('slug', 'why')->firstOrFail();
        $cards = LandingBlock::query()
            ->where('section_slug', 'why')
            ->where('block_type', 'card')
            ->orderBy('sort_order')
            ->get();

        $this->assertSame('Станислав Аристов', $section->extra['quote_author']);
        $this->assertSame('СА', $section->extra['quote_initials']);
        $this->assertCount(6, $cards);
        $this->assertSame('images/functional/request.svg', $cards->first()->extra['icon_asset']);
        $this->assertSame('images/functional/security.svg', $cards->last()->extra['icon_asset']);
    }
}
