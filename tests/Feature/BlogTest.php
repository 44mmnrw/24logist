<?php

namespace Tests\Feature;

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
