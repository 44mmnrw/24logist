<?php

namespace Tests\Feature;

use App\Services\LandingPageService;
use Database\Seeders\LandingContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingMobileStoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_and_driver_content_share_one_manual_carousel(): void
    {
        $this->seed(LandingContentSeeder::class);

        $html = view('components.landing.mobile-story', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertSame(1, substr_count($html, 'class="mobile-story" data-mobile-carousel'));
        $this->assertSame(2, substr_count($html, 'data-mobile-slide='));
        $this->assertSame(2, substr_count($html, 'data-mobile-select='));
        $this->assertStringNotContainsString('data-scroll-story', $html);
        $this->assertStringContainsString('data-mobile-select="mobile"', $html);
        $this->assertStringContainsString('data-mobile-select="driver_cabinet"', $html);
        $this->assertLessThan(
            strpos($html, 'data-mobile-slide="driver_cabinet"'),
            strpos($html, 'data-mobile-slide="mobile"'),
        );
        $this->assertStringContainsString('Удобная мобильная версия', $html);
        $this->assertStringContainsString('Личный кабинет водителя', $html);
    }
}
