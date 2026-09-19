<?php

namespace Tests\Feature;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingWidePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_wide_pricing_renders_one_plan_with_its_active_features(): void
    {
        $section = LandingSection::query()->where('slug', 'pricing_wide')->firstOrFail();
        $plan = LandingBlock::query()->where('section_slug', 'pricing_wide')->where('block_type', 'plan')->firstOrFail();

        $this->assertSame('pricing-wide', $section->anchorId());
        $this->assertSame(1, LandingBlock::query()->where('section_slug', 'pricing_wide')->where('block_type', 'plan')->count());

        $inactiveFeature = LandingBlock::query()->create([
            'section_slug' => 'pricing_wide',
            'block_type' => 'feature',
            'parent_id' => $plan->id,
            'title' => 'Скрытый пункт',
            'is_active' => false,
        ]);
        $this->assertFalse($inactiveFeature->is_active);

        $html = view('components.landing.pricing-wide', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString('pricing-card--wide', $html);
        $this->assertStringContainsString('pricing-card--wide__details', $html);
        $this->assertStringContainsString('pricing-card--wide__features', $html);
        $this->assertStringContainsString('pricing-card--hit', $html);
        $this->assertStringNotContainsString('btn--full', $html);
        $this->assertStringContainsString('По запросу', $html);
        $this->assertStringContainsString('Выделенный менеджер', $html);
        $this->assertStringNotContainsString('Скрытый пункт', $html);
    }
}
