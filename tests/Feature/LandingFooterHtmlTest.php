<?php

namespace Tests\Feature;

use App\Models\LandingSection;
use App\Services\LandingPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingFooterHtmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_renders_safe_html_from_description(): void
    {
        LandingSection::query()->create([
            'slug' => 'footer',
            'name' => 'Подвал',
            'description' => '<strong>Важный текст</strong><br><a href="https://example.com">Подробнее</a><script>alert(1)</script>',
            'is_active' => true,
        ]);

        $html = view('components.landing.footer', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString('landing-footer__description', $html);
        $this->assertStringContainsString('<strong>Важный текст</strong>', $html);
        $this->assertStringContainsString('<a href="https://example.com">Подробнее</a>', $html);
        $this->assertStringNotContainsString('&lt;strong&gt;', $html);
        $this->assertStringNotContainsString('<script', $html);
    }
}
