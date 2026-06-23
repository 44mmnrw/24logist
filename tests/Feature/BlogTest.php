<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
