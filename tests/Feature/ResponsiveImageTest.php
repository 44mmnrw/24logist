<?php

namespace Tests\Feature;

use App\Models\LandingSection;
use App\Support\ImageVariants;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResponsiveImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_hero_prefers_responsive_avif_and_webp_with_original_fallback(): void
    {
        Storage::disk('public')->put('landing/hero/dashboard.png', 'original');
        Storage::disk('public')->put('landing/hero/dashboard--640w.avif', 'avif');
        Storage::disk('public')->put('landing/hero/dashboard--640w.webp', 'webp');
        Storage::disk('public')->put('landing/hero/dashboard--1280w.webp', 'webp-large');

        LandingSection::query()->create([
            'slug' => 'hero',
            'name' => 'Главный экран',
            'title' => 'ЛогистРу',
            'seo_h1' => 'CRM для экспедиторов',
            'is_active' => true,
            'sort_order' => 1,
            'extra' => [
                'carousel_slides' => [
                    [
                        'image' => 'landing/hero/dashboard.png',
                        'alt' => 'Интерфейс ЛогистРу',
                    ],
                ],
            ],
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('type="image/avif"', false)
            ->assertSee('/storage/landing/hero/dashboard--640w.avif 640w', false)
            ->assertSee('type="image/webp"', false)
            ->assertSee(
                '/storage/landing/hero/dashboard--640w.webp 640w, /storage/landing/hero/dashboard--1280w.webp 1280w',
                false,
            )
            ->assertSee('src="/storage/landing/hero/dashboard.png"', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('loading="eager"', false);
    }

    public function test_variant_paths_are_not_treated_as_source_images(): void
    {
        $this->assertTrue(ImageVariants::isOptimizableOriginal('landing/hero/dashboard.png'));
        $this->assertFalse(ImageVariants::isOptimizableOriginal('landing/hero/dashboard--640w.webp'));
        $this->assertFalse(ImageVariants::isOptimizableOriginal('landing/hero/dashboard--640w.avif'));
    }
}
