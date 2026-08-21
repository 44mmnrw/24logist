<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\BlogTags\BlogTagResource;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\BlogTagSocialImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_blog_index_shows_published_posts(): void
    {
        BlogPost::query()->create([
            'title' => 'Автоматизация рейсов',
            'slug' => 'automation-routes',
            'excerpt' => 'Как контролировать рейсы и документы.',
            'body' => 'Текст статьи',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        BlogPost::query()->create([
            'title' => 'Черновик',
            'slug' => 'draft-post',
            'body' => 'Не виден',
            'is_published' => false,
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('Автоматизация рейсов');
        $response->assertDontSee('Черновик');
    }

    public function test_blog_preview_excerpt_is_shortened_without_changing_full_excerpt(): void
    {
        $excerpt = str_repeat('Длинный анонс статьи для карточки. ', 20);
        $post = new BlogPost(['excerpt' => $excerpt]);

        $this->assertSame(trim($excerpt), $post->displayExcerpt());
        $this->assertLessThanOrEqual(220, mb_strlen((string) $post->previewExcerpt()));
        $this->assertStringEndsWith('…', (string) $post->previewExcerpt());
    }

    public function test_blog_index_orders_posts_by_publication_date_not_manual_sort_order(): void
    {
        BlogPost::query()->create([
            'title' => 'Старая статья',
            'slug' => 'old-post',
            'body' => 'Текст',
            'is_published' => true,
            'published_at' => now()->subDays(3),
            'sort_order' => 0,
        ]);

        BlogPost::query()->create([
            'title' => 'Новая статья',
            'slug' => 'new-post',
            'body' => 'Текст',
            'is_published' => true,
            'published_at' => now()->subDay(),
            'sort_order' => 999,
        ]);

        BlogPost::query()->create([
            'title' => 'Средняя статья',
            'slug' => 'middle-post',
            'body' => 'Текст',
            'is_published' => true,
            'published_at' => now()->subDays(2),
            'sort_order' => -999,
        ]);

        $this->get('/blog')
            ->assertOk()
            ->assertSeeInOrder(['Новая статья', 'Средняя статья', 'Старая статья']);
    }

    public function test_blog_card_uses_prepared_image_and_optional_logo_overlay(): void
    {
        BlogPost::query()->create([
            'title' => 'Card image test',
            'slug' => 'prepared-card',
            'body' => 'Article body',
            'cover_image_path' => 'blog/covers/original.jpg',
            'card_image_path' => 'blog/cards/prepared.webp',
            'show_card_logo' => true,
            'card_logo_position' => 'top-right',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('/storage/blog/cards/prepared.webp', false)
            ->assertSee('blog-card__media--branded', false)
            ->assertSee('blog-logo--top-right', false)
            ->assertDontSee('/storage/blog/covers/original.jpg', false);
    }

    public function test_blog_index_shows_fixed_category_name(): void
    {
        $category = BlogCategory::query()->create([
            'name' => 'Обновления',
            'slug' => 'updates',
            'is_active' => true,
        ]);

        BlogPost::query()->create([
            'title' => 'Обновление платформы',
            'slug' => 'platform-update',
            'body' => 'Текст статьи',
            'blog_category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('Обновления');
    }

    public function test_blog_index_shows_clear_links_only_for_active_categories_with_published_posts(): void
    {
        $visibleCategory = BlogCategory::query()->create([
            'name' => 'Useful guides',
            'slug' => 'useful-guides',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $draftOnlyCategory = BlogCategory::query()->create([
            'name' => 'Draft only',
            'slug' => 'draft-only',
            'is_active' => true,
        ]);
        $inactiveCategory = BlogCategory::query()->create([
            'name' => 'Inactive category',
            'slug' => 'inactive-category',
            'is_active' => false,
        ]);

        foreach (['first-guide', 'second-guide'] as $slug) {
            BlogPost::query()->create([
                'title' => $slug,
                'slug' => $slug,
                'body' => 'Article body',
                'blog_category_id' => $visibleCategory->id,
                'is_published' => true,
                'published_at' => now()->subDay(),
            ]);
        }

        BlogPost::query()->create([
            'title' => 'Draft article',
            'slug' => 'draft-category-article',
            'body' => 'Article body',
            'blog_category_id' => $draftOnlyCategory->id,
            'is_published' => false,
        ]);
        BlogPost::query()->create([
            'title' => 'Inactive category article',
            'slug' => 'inactive-category-article',
            'body' => 'Article body',
            'blog_category_id' => $inactiveCategory->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Навигация по блогу')
            ->assertSee('href="'.e($visibleCategory->getUrl()).'"', false)
            ->assertSee('Useful guides')
            ->assertSee('2 материала')
            ->assertDontSee('Draft only')
            ->assertDontSee('href="'.e($inactiveCategory->getUrl()).'"', false);
    }

    public function test_blog_category_is_clickable_and_category_page_filters_published_posts(): void
    {
        $category = BlogCategory::query()->create([
            'name' => 'Automation',
            'slug' => 'automation',
            'is_active' => true,
        ]);
        $categoryUrl = $category->getUrl();

        $publishedPost = BlogPost::query()->create([
            'title' => 'Published category post',
            'slug' => 'published-category-post',
            'body' => 'Article body',
            'blog_category_id' => $category->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        BlogPost::query()->create([
            'title' => 'Hidden category post',
            'slug' => 'hidden-category-post',
            'body' => 'Article body',
            'blog_category_id' => $category->id,
            'is_published' => false,
        ]);

        $this->get($publishedPost->getUrl())
            ->assertOk()
            ->assertSee('href="'.e($categoryUrl).'"', false);

        $this->get($categoryUrl)
            ->assertOk()
            ->assertSee('Published category post')
            ->assertDontSee('Hidden category post');
    }

    public function test_blog_category_renders_full_seo_settings_and_inactive_category_is_hidden(): void
    {
        $category = BlogCategory::query()->create([
            'name' => 'Digital logistics',
            'slug' => 'digital-logistics',
            'description' => 'Category description',
            'seo_h1' => 'Digital logistics articles',
            'meta_title' => 'Category SEO title',
            'meta_description' => 'Category SEO description',
            'meta_keywords' => 'logistics, automation',
            'meta_robots' => 'index, follow',
            'og_title' => 'Category OG title',
            'og_description' => 'Category OG description',
            'twitter_title' => 'Category Twitter title',
            'twitter_description' => 'Category Twitter description',
            'schema_type' => 'CollectionPage',
            'schema_headline' => 'Category schema headline',
            'schema_description' => 'Category schema description',
            'is_active' => true,
        ]);

        $this->get($category->getUrl())
            ->assertOk()
            ->assertSee('<h1>Digital logistics articles</h1>', false)
            ->assertSee('<title>Category SEO title', false)
            ->assertSee('name="description" content="Category OG description"', false)
            ->assertSee('name="keywords" content="logistics, automation"', false)
            ->assertSee('property="og:title" content="Category OG title"', false)
            ->assertSee('name="twitter:title" content="Category Twitter title"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('Category schema headline');

        $category->update(['is_active' => false]);

        $this->get($category->getUrl())->assertNotFound();
    }

    public function test_blog_post_page_has_article_seo(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'SEO для статьи',
            'slug' => 'seo-post',
            'excerpt' => 'Описание статьи для поиска.',
            'body' => 'Текст статьи',
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get($post->getUrl());

        $response->assertOk();
        $response->assertSee('SEO title');
        $response->assertSee('SEO description');
        $response->assertSee('Article');
        $response->assertSee('/blog/seo-post');
    }

    public function test_blog_post_cover_shows_logo_when_enabled(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Branded article cover',
            'slug' => 'branded-article-cover',
            'body' => 'Article body',
            'cover_image_path' => 'blog/covers/cover.jpg',
            'show_card_logo' => true,
            'card_logo_position' => 'bottom-right',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get($post->getUrl())
            ->assertOk()
            ->assertSee('blog-post-cover--branded', false)
            ->assertSee('blog-logo--bottom-right', false);
    }

    public function test_blog_post_page_outputs_extended_seo_metadata(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Новости рынка',
            'slug' => 'market-news',
            'body' => 'Текст новости',
            'author_name' => 'Редакция',
            'author_type' => 'Organization',
            'author_url' => 'https://24logist.ru/pages/about',
            'twitter_title' => 'Twitter title',
            'twitter_description' => 'Twitter description',
            'twitter_card' => 'summary',
            'schema_type' => 'NewsArticle',
            'schema_headline' => 'Schema headline',
            'schema_description' => 'Schema description',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get($post->getUrl());

        $response->assertOk();
        $response->assertSee('name="twitter:card" content="summary"', false);
        $response->assertSee('name="twitter:title" content="Twitter title"', false);
        $response->assertSee('name="twitter:description" content="Twitter description"', false);
        $response->assertSee('"@type":"NewsArticle"', false);
        $response->assertSee('"headline":"Schema headline"', false);
        $response->assertSee('"description":"Schema description"', false);
        $response->assertSee('"@type":"Organization"', false);
        $response->assertSee('"url":"https://24logist.ru/pages/about"', false);
    }

    public function test_unpublished_blog_post_returns_404(): void
    {
        BlogPost::query()->create([
            'title' => 'Не опубликовано',
            'slug' => 'hidden-post',
            'body' => 'Текст статьи',
            'is_published' => false,
        ]);

        $this->get('/blog/hidden-post')->assertNotFound();
    }

    public function test_changing_article_slug_creates_direct_permanent_redirects(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Slug redirect test',
            'slug' => 'first-article-url',
            'body' => 'Article body',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $post->update(['slug' => 'second-article-url']);
        $post->update(['slug' => 'current-article-url']);

        $currentUrl = route('blog.show', 'current-article-url');

        $this->get('/blog/first-article-url')
            ->assertStatus(301)
            ->assertRedirect($currentUrl);

        $this->get('/blog/second-article-url')
            ->assertStatus(301)
            ->assertRedirect($currentUrl);

        $this->get('/blog/current-article-url')->assertOk();
        $this->assertDatabaseHas('blog_post_redirects', [
            'blog_post_id' => $post->id,
            'slug' => 'first-article-url',
        ]);
    }

    public function test_article_cannot_use_another_articles_redirect_slug(): void
    {
        $firstPost = BlogPost::query()->create([
            'title' => 'First article',
            'slug' => 'reserved-old-url',
            'body' => 'Article body',
        ]);
        $firstPost->update(['slug' => 'first-current-url']);

        $secondPost = BlogPost::query()->create([
            'title' => 'Second article',
            'slug' => 'second-current-url',
            'body' => 'Article body',
        ]);

        $this->expectException(ValidationException::class);

        $secondPost->update(['slug' => 'reserved-old-url']);
    }

    public function test_old_slug_of_unpublished_article_returns_404(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Unpublished redirect',
            'slug' => 'unpublished-old-url',
            'body' => 'Article body',
            'is_published' => false,
        ]);

        $post->update(['slug' => 'unpublished-current-url']);

        $this->get('/blog/unpublished-old-url')->assertNotFound();
    }

    public function test_blog_tags_are_clickable_and_tag_page_filters_published_posts(): void
    {
        $matchingPost = BlogPost::query()->create([
            'title' => 'Автоматизация доставки',
            'slug' => 'delivery-automation',
            'body' => 'Текст статьи',
            'tags' => ['Автоматизация', 'Доставка'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        BlogPost::query()->create([
            'title' => 'Управление складом',
            'slug' => 'warehouse-management',
            'body' => 'Другой текст',
            'tags' => ['Склад'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        BlogPost::query()->create([
            'title' => 'Скрытая автоматизация',
            'slug' => 'hidden-automation',
            'body' => 'Черновик',
            'tags' => ['Автоматизация'],
            'is_published' => false,
        ]);

        $tag = BlogTag::query()->where('name', 'Автоматизация')->firstOrFail();
        $tagUrl = $tag->getUrl();

        $this->get($matchingPost->getUrl())
            ->assertOk()
            ->assertSee('href="'.e($tagUrl).'"', false);

        $this->get($tagUrl)
            ->assertOk()
            ->assertSee('Статьи с тегом «Автоматизация»')
            ->assertSee('Автоматизация доставки')
            ->assertDontSee('Управление складом')
            ->assertDontSee('Скрытая автоматизация');
    }

    public function test_empty_tag_redirects_to_blog_index(): void
    {
        $this->get('/tag')->assertRedirect(route('blog.index'));
    }

    public function test_legacy_tag_url_redirects_to_permanent_slug_and_seo_settings_are_rendered(): void
    {
        BlogPost::query()->create([
            'title' => 'SEO для тега',
            'slug' => 'tag-seo-post',
            'body' => 'Текст',
            'tags' => ['Автоматизация'],
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $tag = BlogTag::query()->where('name', 'Автоматизация')->firstOrFail();
        $tag->update([
            'slug' => 'automation',
            'seo_h1' => 'Автоматизация транспортной логистики',
            'meta_title' => 'Автоматизация логистики — статьи',
            'meta_description' => 'Подборка материалов об автоматизации логистики.',
            'meta_robots' => 'index, follow, max-image-preview:large',
            'og_title' => 'Автоматизация логистики',
            'schema_type' => 'CollectionPage',
        ]);

        $this->get('/tag?tag='.urlencode('Автоматизация'))
            ->assertRedirect($tag->getUrl())
            ->assertStatus(301);

        $this->get($tag->getUrl())
            ->assertOk()
            ->assertSee('<h1>Автоматизация транспортной логистики</h1>', false)
            ->assertSee('<title>Автоматизация логистики — статьи', false)
            ->assertSee('name="description" content="Подборка материалов об автоматизации логистики."', false)
            ->assertSee('rel="canonical" href="'.$tag->getUrl().'"', false)
            ->assertSee('property="og:title" content="Автоматизация логистики"', false)
            ->assertSee('"@type":"CollectionPage"', false);
    }

    public function test_blog_post_renders_article_format_blocks(): void
    {
        $post = BlogPost::query()->create([
            'title' => 'Статейная верстка',
            'slug' => 'article-layout',
            'body' => '<p class="lead">Вводный абзац</p><h2>Раздел</h2><h3>Подраздел</h3><blockquote><p>Главное</p></blockquote><table><tbody><tr><td>45 дней</td><td>3 месяца</td></tr></tbody></table>',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get($post->getUrl());

        $response->assertOk();
        $response->assertSee('blog-post-body--article', false);
        $response->assertSee('<h2>Раздел</h2>', false);
        $response->assertSee('<blockquote>', false);
        $response->assertSee('<table>', false);
    }

    public function test_only_unused_blog_tags_can_be_deleted(): void
    {
        BlogPost::query()->create([
            'title' => 'Статья с тегом',
            'slug' => 'post-with-protected-tag',
            'body' => 'Текст',
            'tags' => ['Используемый тег'],
            'is_published' => true,
        ]);

        $usedTag = BlogTag::query()->where('name', 'Используемый тег')->firstOrFail();
        $unusedTag = BlogTag::query()->create(['name' => 'Свободный тег', 'slug' => 'free-tag']);

        $this->assertTrue($usedTag->isUsed());
        $this->assertSame(1, $usedTag->usageCount());
        $this->assertFalse(BlogTagResource::canDelete($usedTag));
        $this->assertTrue(BlogTagResource::canDelete($unusedTag));

        try {
            $usedTag->delete();
            $this->fail('Used tag deletion must be blocked.');
        } catch (ValidationException $exception) {
            $this->assertSame('Нельзя удалить тег, который используется в статьях.', $exception->errors()['tag'][0]);
        }

        $this->assertDatabaseHas('blog_tags', ['id' => $usedTag->id]);

        $unusedTag->delete();
        $this->assertDatabaseMissing('blog_tags', ['id' => $unusedTag->id]);
    }

    public function test_social_image_is_generated_for_blog_tag_and_used_by_all_seo_formats(): void
    {
        Storage::fake('public');

        $tag = BlogTag::query()->create([
            'name' => 'Электронные транспортные накладные',
            'slug' => 'elektronnye-transportnye-nakladnye',
            'seo_h1' => 'Электронные транспортные накладные для перевозчиков',
            'social_image_title' => 'ЭТрН для грузоперевозок',
        ]);

        $path = app(BlogTagSocialImageGenerator::class)->generate($tag);

        Storage::disk('public')->assertExists($path);
        $this->assertSame([1200, 630], array_slice(getimagesize(Storage::disk('public')->path($path)), 0, 2));
        $this->assertSame('ЭТрН для грузоперевозок', $tag->socialImageTitle());
        $this->assertSame($path, $tag->fresh()->og_image_path);
        $this->assertSame($path, $tag->fresh()->twitter_image_path);
        $this->assertSame($path, $tag->fresh()->schema_image_path);
    }
}
