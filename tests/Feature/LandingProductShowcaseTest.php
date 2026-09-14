<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
use App\Models\LandingSection;
use App\Models\User;
use App\Services\LandingPageService;
use App\Support\LandingProductShowcaseForm;
use Database\Seeders\LandingContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LandingProductShowcaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LandingContentSeeder::class);
    }

    public function test_first_banner_is_rendered_immediately_before_pricing(): void
    {
        $showcase = LandingSection::query()->where('slug', 'product_showcase')->firstOrFail();
        $pricing = LandingSection::query()->where('slug', 'pricing')->firstOrFail();
        $this->assertLessThan($pricing->sort_order, $showcase->sort_order);

        $html = view('components.landing.product-showcase', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertSame(1, substr_count($html, 'data-product-slide'));
        $this->assertStringContainsString('data-product-carousel', $html);
        $this->assertStringNotContainsString('data-product-select', $html);
        $this->assertStringNotContainsString('data-scroll-story', $html);
        $this->assertStringContainsString('список заявок', $html);
        $this->assertStringContainsString('указанием менеджера, статуса документа и быстрыми действиями', $html);
        $this->assertStringContainsString('id="product-showcase"', $html);
    }

    public function test_admin_banner_fields_keep_order_and_uploaded_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('landing/product-showcase/screen.webp', 'image');

        $section = LandingSection::query()->where('slug', 'product_showcase')->firstOrFail();
        $form = LandingProductShowcaseForm::hydrate($section->toArray());
        $this->assertSame('список заявок', $form['product_showcase_banners'][0]['title']);
        $this->assertSame([], $form['product_showcase_banners'][0]['image']);

        $form['product_showcase_banners'] = [
            [
                'title' => 'Второй баннер',
                'description' => 'Описание второго',
                'image' => ['landing/product-showcase/screen.webp'],
                'alt' => 'Скриншот заявок',
            ],
            $form['product_showcase_banners'][0],
        ];
        $saved = LandingProductShowcaseForm::dehydrate($form);

        $this->assertArrayNotHasKey('product_showcase_banners', $saved);
        $this->assertSame('Второй баннер', $saved['extra']['banners'][0]['title']);
        $this->assertSame('landing/product-showcase/screen.webp', $saved['extra']['banners'][0]['image']);
        $this->assertSame('список заявок', $saved['extra']['banners'][1]['title']);
        $this->assertNull($saved['extra']['banners'][1]['image']);

        $section->update(['extra' => $saved['extra']]);
        app(LandingPageService::class)->clearCache();

        $html = view('components.landing.product-showcase', [
            'landing' => app(LandingPageService::class),
        ])->render();

        $this->assertSame(2, substr_count($html, 'data-product-slide'));
        $this->assertSame(2, substr_count($html, 'data-product-select'));
        $this->assertStringContainsString('loading="eager"', $html);
        $this->assertLessThan(strpos($html, 'список заявок'), strpos($html, 'Второй баннер'));
        $this->assertStringContainsString('Скриншот заявок', $html);
    }

    public function test_admin_can_edit_the_first_banner_copy(): void
    {
        $this->actingAs(User::factory()->create());
        $section = LandingSection::query()->where('slug', 'product_showcase')->firstOrFail();

        $component = Livewire::test(EditLandingSection::class, ['record' => $section->getRouteKey()]);
        $key = array_key_first($component->get('data.product_showcase_banners'));

        $component
            ->set("data.product_showcase_banners.{$key}.title", 'Отредактированный заголовок')
            ->set("data.product_showcase_banners.{$key}.description", 'Отредактированное описание')
            ->call('save')
            ->assertHasNoFormErrors();

        $banner = $section->fresh()->extra['banners'][0];
        $this->assertSame('Отредактированный заголовок', $banner['title']);
        $this->assertSame('Отредактированное описание', $banner['description']);
    }
}
