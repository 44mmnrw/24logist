<?php

namespace Tests\Feature;

use App\Models\LandingSection;
use App\Services\LandingPageService;
use Database\Seeders\LandingRouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_deploy_seeder_initializes_route_only_once(): void
    {
        foreach (['hero', 'why', 'platform', 'pricing', 'epd_platform', 'mobile', 'driver_cabinet', 'quiz', 'final_cta', 'faq'] as $index => $slug) {
            LandingSection::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $slug,
                    'route_enabled' => null,
                    'route_label' => null,
                    'sort_order' => $index,
                ],
            );
        }

        $this->seed(LandingRouteSeeder::class);

        $hero = LandingSection::query()->where('slug', 'hero')->firstOrFail();
        $faq = LandingSection::query()->where('slug', 'faq')->firstOrFail();

        $this->assertTrue($hero->route_enabled);
        $this->assertSame('Старт', $hero->route_label);
        $this->assertFalse($faq->route_enabled);
        $this->assertNull($faq->route_label);

        $hero->update([
            'route_enabled' => false,
            'route_label' => null,
        ]);

        $this->seed(LandingRouteSeeder::class);

        $hero->refresh();
        $this->assertFalse($hero->route_enabled);
        $this->assertNull($hero->route_label);
    }

    public function test_server_html_contains_only_enabled_labeled_stops_in_sort_order(): void
    {
        LandingSection::query()->update(['route_enabled' => false, 'route_label' => null]);

        $this->section('hero', 30, true, 'Третья точка');
        $this->section('why', 10, true, 'Первая точка');
        $this->section('platform', 20, false, 'Скрытая точка');
        $this->section('final_cta', 40, true, '');

        app(LandingPageService::class)->clearCache();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('data-landing-route', false)
            ->assertSee('landing-route__path--road-marking', false)
            ->assertSee('data-landing-route-progress', false)
            ->assertSeeInOrder(['Первая точка', 'Третья точка'])
            ->assertDontSee('data-route-label="Скрытая точка"', false)
            ->assertDontSee('data-route-label=""', false);

        $this->assertSame(2, substr_count($response->getContent(), 'data-route-stop'));
    }

    public function test_route_labels_are_not_hardcoded_in_the_landing_template(): void
    {
        $template = file_get_contents(resource_path('views/welcome.blade.php'));

        foreach (['Старт', 'Функционал', 'Платформа', 'Тарифы', 'ЭПД', 'Мобильный кабинет', 'ЛК водителя', 'Подбор тарифа', 'Финиш'] as $label) {
            $this->assertStringNotContainsString($label, $template);
        }
    }

    private function section(string $slug, int $sortOrder, bool $enabled, string $label): LandingSection
    {
        return LandingSection::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $slug,
                'title' => $slug,
                'route_enabled' => $enabled,
                'route_label' => $label,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ],
        );
    }
}
