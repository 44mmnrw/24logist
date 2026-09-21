<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
use App\Filament\Clusters\Landing\Resources\LandingSections\RelationManagers\BlocksRelationManager;
use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Models\User;
use App\Services\LandingPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LandingWidePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_wide_pricing_renders_four_sections_with_configurable_options(): void
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
        $this->assertStringContainsString('pricing-card--wide__additional-heading', $html);
        $this->assertStringContainsString('pricing-card--wide__configurator', $html);
        $this->assertStringContainsString('pricing-card--hit', $html);
        $this->assertStringNotContainsString('btn--full', $html);
        $this->assertStringContainsString('data-base-price="1200"', $html);
        $this->assertStringContainsString('data-wide-pricing-users', $html);
        $this->assertSame(1, substr_count($html, 'pricing-card--wide__users'));
        $this->assertLessThan(
            strpos($html, 'pricing-card--wide__additional-heading'),
            strpos($html, 'pricing-card--wide__users'),
        );
        $this->assertStringContainsString('data-wide-pricing-users-note', $html);
        $this->assertStringContainsString('data-wide-pricing-period-button', $html);
        $this->assertStringContainsString('data-period="month"', $html);
        $this->assertStringContainsString('data-period="year"', $html);
        $this->assertStringContainsString('data-year-currency-suffix="₽/год"', $html);
        $this->assertStringContainsString('data-wide-pricing-option-price', $html);
        $this->assertStringContainsString('data-user-count-labels=', $html);
        $this->assertStringContainsString('за одно рабочее место', $html);
        $this->assertStringContainsString('за двадцать рабочих мест', $html);
        $this->assertStringContainsString('data-wide-pricing-total', $html);
        $this->assertStringContainsString('data-wide-pricing-decrease', $html);
        $this->assertStringContainsString('data-wide-pricing-increase', $html);
        $this->assertStringContainsString('data-wide-pricing-option', $html);
        $this->assertStringContainsString('Дополнительные возможности', $html);
        $this->assertStringContainsString('Модуль ЭПД', $html);
        $this->assertStringContainsString('Получить предложение', $html);
        $this->assertStringContainsString('Выделенный менеджер', $html);
        $this->assertStringNotContainsString('Скрытый пункт', $html);
    }

    public function test_wide_pricing_calculator_is_editable_in_admin(): void
    {
        $section = LandingSection::query()->where('slug', 'pricing_wide')->firstOrFail();
        $plan = LandingBlock::query()->where('section_slug', 'pricing_wide')->where('block_type', 'plan')->firstOrFail();

        $this->actingAs(User::factory()->create());

        Livewire::test(BlocksRelationManager::class, [
            'ownerRecord' => $section,
            'pageClass' => EditLandingSection::class,
        ])->callTableAction('edit', $plan, [
            'title' => 'Команда',
            'subtitle' => 'Гибкий тариф',
            'price' => 1750,
            'description' => 'за пользователя',
            'wide_currency_suffix' => '₽/мес',
            'wide_year_currency_suffix' => '₽/год',
            'wide_additional_title' => 'Добавьте нужное',
            'wide_users_label' => 'Пользователи',
            'wide_users_min' => 2,
            'wide_users_max' => 10,
            'wide_users_default' => 3,
            'tag' => 'Выгодно',
            'plan_features' => [
                ['title' => 'Базовая возможность'],
            ],
            'plan_options' => [
                ['title' => 'Дополнительный модуль', 'price' => 900],
            ],
            'button_text' => 'Оставить заявку',
            'link' => '/pages/contacts',
            'button_style' => 'primary',
            'is_highlighted' => true,
            'is_active' => true,
            'sort_order' => 1,
        ])->assertHasNoActionErrors();

        $plan->refresh();
        $this->assertSame('1750', $plan->price);
        $this->assertSame('Добавьте нужное', $plan->extra['additional_title']);
        $this->assertSame(3, $plan->extra['users_default']);
        $this->assertSame('₽/год', $plan->extra['year_currency_suffix']);
        $this->assertSame(
            '900',
            $plan->children()->where('block_type', 'paid_option')->where('title', 'Дополнительный модуль')->value('price'),
        );
    }
}
