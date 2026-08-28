<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\LandingSection;
use App\Support\BlogBodyImages;
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

    public function test_blog_cards_prefer_avif_with_webp_and_original_fallbacks(): void
    {
        Storage::disk('public')->put('blog/cards/card.webp', 'original');
        Storage::disk('public')->put('blog/cards/card--640w.avif', 'avif');
        Storage::disk('public')->put('blog/cards/card--640w.webp', 'webp');

        BlogPost::query()->create([
            'title' => 'Responsive blog card',
            'slug' => 'responsive-blog-card',
            'body' => 'Article body',
            'card_image_path' => 'blog/cards/card.webp',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('type="image/avif"', false)
            ->assertSee('/storage/blog/cards/card--640w.avif 640w', false)
            ->assertSee('type="image/webp"', false)
            ->assertSee('/storage/blog/cards/card--640w.webp 640w', false)
            ->assertSee('src="/storage/blog/cards/card.webp"', false);
    }

    public function test_article_body_images_prefer_responsive_avif_and_webp(): void
    {
        Storage::disk('public')->put('blog/body/diagram.png', 'original');
        Storage::disk('public')->put('blog/body/diagram--480w.avif', 'avif');
        Storage::disk('public')->put('blog/body/diagram--480w.webp', 'webp');
        Storage::disk('public')->put('blog/body/diagram--960w.avif', 'avif-large');

        $post = BlogPost::query()->create([
            'title' => 'Responsive article image',
            'slug' => 'responsive-article-image',
            'body' => '<p><img data-id="blog/body/diagram.png" alt="Article diagram"></p>',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->assertSame(['blog/body/diagram.png'], BlogBodyImages::paths($post->body));

        $this->get($post->getUrl())
            ->assertOk()
            ->assertSee('<picture class="blog-post-body__picture">', false)
            ->assertSee('type="image/avif"', false)
            ->assertSee('/storage/blog/body/diagram--480w.avif 480w', false)
            ->assertSee('/storage/blog/body/diagram--960w.avif 960w', false)
            ->assertSee('type="image/webp"', false)
            ->assertSee('/storage/blog/body/diagram--480w.webp 480w', false)
            ->assertSee('src="/storage/blog/body/diagram.png"', false);
    }

    public function test_article_body_image_paths_are_restricted_to_the_body_directory(): void
    {
        $content = '<img data-id="blog/body/first.jpg"><img src="/storage/blog/body/second.png?version=2"><img data-id="blog/covers/not-body.jpg"><img src="https://example.com/external.jpg">';

        $this->assertSame([
            'blog/body/first.jpg',
            'blog/body/second.png',
        ], BlogBodyImages::paths($content));
    }

    public function test_committed_blog_images_can_use_public_avif_variants(): void
    {
        $image = ImageVariants::data('images/blog/ekspeditor-2026/cover.png');

        $this->assertStringContainsString('cover--640w.avif 640w', (string) $image['avif_srcset']);
        $this->assertStringContainsString('cover--1280w.webp 1280w', (string) $image['webp_srcset']);
        $this->assertStringEndsWith('/images/blog/ekspeditor-2026/cover.png', (string) $image['url']);
    }
}
