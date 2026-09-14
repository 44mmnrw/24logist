<?php

namespace Tests\Feature;

use App\Models\LandingSection;
use App\Models\SiteSetting;
use App\Services\LandingPageService;
use App\Services\SiteSettingsService;
use App\Support\LandingGrowthForm;
use Database\Seeders\GrowthSectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingGrowthSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GrowthSectionSeeder::class);
    }

    public function test_growth_section_renders_copy_and_dashboard_cards(): void
    {
        $this->assertSame('growth', LandingSection::query()->where('slug', 'growth')->firstOrFail()->anchorId());

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

    public function test_configured_route_calculator_is_the_first_growth_panel(): void
    {
        $secret = 'shared-route-api-secret-at-least-32-characters';
        SiteSetting::instance()->update([
            'route_calculator_enabled' => true,
            'route_api_base_url' => 'https://platform.example.test/api/internal/site/routes',
            'route_api_secret' => $secret,
        ]);
        app(SiteSettingsService::class)->clearCache();

        $html = view('components.landing.growth', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString('data-growth-slide="route"', $html);
        $this->assertStringContainsString('data-growth-track', $html);
        $this->assertStringContainsString('data-route-calculator-calculator', $html);
        $this->assertMatchesRegularExpression('/data-growth-slide="efficiency"[^>]*aria-hidden="true"[^>]*\binert\b/', $html);
        $this->assertStringContainsString('data-growth-select="efficiency"', $html);
        $this->assertSame(2, substr_count($html, 'class="growth-carousel__dot'));
        $this->assertStringNotContainsString('growth-carousel__arrow', $html);
        $this->assertLessThan(
            strpos($html, 'data-growth-slide="efficiency"'),
            strpos($html, 'data-growth-slide="route"'),
        );
        $this->assertStringNotContainsString($secret, $html);
    }

    public function test_all_visible_dashboard_content_is_loaded_from_the_database(): void
    {
        $section = LandingSection::query()->where('slug', 'growth')->firstOrFail();
        $extra = $section->extra;
        $extra['chart_title'] = 'Редактируемый заголовок диаграммы';
        $extra['tab_count_label'] = 'Редактируемая вкладка';
        $extra['margin_segments'][0]['label'] = 'Редактируемый диапазон';
        $extra['margin_segments'][0]['color'] = '#123456';
        $extra['customer_metrics'][0]['name'] = 'Редактируемая компания';
        $extra['customer_metrics'][0]['count_value'] = '77';
        $section->extra = $extra;
        $section->save();

        $html = view('components.landing.growth', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertStringContainsString('Редактируемый заголовок диаграммы', $html);
        $this->assertStringContainsString('Редактируемая вкладка', $html);
        $this->assertStringContainsString('Редактируемый диапазон', $html);
        $this->assertStringContainsString('#123456', $html);
        $this->assertStringContainsString('Редактируемая компания', $html);
        $this->assertStringContainsString('<strong>77</strong>', $html);
    }

    public function test_deploy_seeder_creates_growth_content_and_preserves_admin_edits(): void
    {
        LandingSection::query()->where('slug', 'growth')->delete();

        $this->seed(GrowthSectionSeeder::class);

        $section = LandingSection::query()->where('slug', 'growth')->firstOrFail();
        $this->assertStringContainsString('Работа в нашей системе освободит', $section->description);
        $this->assertArrayNotHasKey('paragraph_one', $section->extra);
        $this->assertSame('Сегменты маржинальности заявок', $section->extra['chart_title']);
        $this->assertCount(5, $section->extra['customer_metrics']);
        $this->assertSame('#c32108', $section->extra['margin_segments'][0]['color']);

        $extra = $section->extra;
        $extra['chart_title'] = 'Текст из админки';
        unset($extra['customers_title']);
        $section->extra = $extra;
        $section->save();

        $this->seed(GrowthSectionSeeder::class);

        $section->refresh();
        $this->assertSame('Текст из админки', $section->extra['chart_title']);
        $this->assertSame('Топ заказчиков', $section->extra['customers_title']);
    }

    public function test_deploy_seeder_fills_empty_customer_report_fields(): void
    {
        $section = LandingSection::query()->where('slug', 'growth')->firstOrFail();
        $extra = $section->extra;
        $extra['customers_title'] = '';
        $extra['customer_metrics'] = [];
        $section->extra = $extra;
        $section->save();

        $this->seed(GrowthSectionSeeder::class);

        $section->refresh();
        $this->assertSame('Топ заказчиков', $section->extra['customers_title']);
        $this->assertCount(5, $section->extra['customer_metrics']);
        $this->assertSame('ООО "ГК «ЛОГОС»"', $section->extra['customer_metrics'][0]['name']);
    }

    public function test_admin_form_is_hydrated_from_and_saved_to_extra_json(): void
    {
        $section = LandingSection::query()->where('slug', 'growth')->firstOrFail();

        $formData = LandingGrowthForm::hydrate($section->toArray());

        $this->assertStringContainsString('Работа в нашей системе освободит', $formData['description']);
        $this->assertSame('Сегменты маржинальности заявок', $formData['growth_chart_title']);
        $this->assertCount(4, $formData['growth_margin_segments']);
        $this->assertCount(5, $formData['growth_customer_metrics']);

        $formData['growth_chart_title'] = 'Изменено в форме админки';
        $savedData = LandingGrowthForm::dehydrate($formData);

        $this->assertSame('Изменено в форме админки', $savedData['extra']['chart_title']);
        $this->assertArrayNotHasKey('growth_chart_title', $savedData);
    }
}
