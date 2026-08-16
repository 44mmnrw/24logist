<?php

namespace Tests\Feature;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPricingBadgesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_plan_renders_both_editable_badges(): void
    {
        LandingSection::query()->create([
            'slug' => 'pricing',
            'name' => 'Тарифы',
            'title' => 'Тарифы',
            'is_active' => true,
        ]);

        LandingBlock::query()->create([
            'section_slug' => 'pricing',
            'block_type' => 'plan',
            'title' => 'Стандарт',
            'price' => '3 900 ₽/мес',
            'tag' => 'Хит',
            'secondary_tag' => 'Выгодно',
            'is_active' => true,
        ]);

        $html = view('components.landing.pricing', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString('pricing-card__badges', $html);
        $this->assertStringContainsString('>Хит</span>', $html);
        $this->assertStringContainsString('pricing-hit pricing-hit--secondary', $html);
        $this->assertStringContainsString('>Выгодно</span>', $html);
    }
}
