<?php

namespace Tests\Feature;

use App\Services\LandingPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingGrowthSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_growth_section_renders_copy_and_dashboard_cards(): void
    {
        $html = view('components.landing.growth', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString('class="growth-section"', $html);
        $this->assertStringContainsString('Повышайте эффективность и растите вместе с нами', $html);
        $this->assertStringContainsString('30 до 60% времени', $html);
        $this->assertStringContainsString('Сегменты маржинальности заявок', $html);
        $this->assertStringContainsString('class="growth-donut"', $html);
        $this->assertStringContainsString('data-growth-unit="count"', $html);
        $this->assertStringContainsString('data-growth-view="revenue"', $html);
        $this->assertStringContainsString('data-growth-customer-data', $html);
        $this->assertStringContainsString('ООО &quot;ГК «ЛОГОС»&quot;', $html);
        $this->assertSame(5, substr_count($html, 'class="growth-customer"'));
    }
}
