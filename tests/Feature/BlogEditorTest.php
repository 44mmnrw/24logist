<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Clusters\Landing\Resources\BlogPosts\Pages\EditBlogPost;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BlogEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_cover_automatically_creates_branded_card_image(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }

        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBlogPost::class)
            ->fillForm([
                'title' => 'Automatic card image',
                'slug' => 'automatic-card-image',
                'body' => 'Article body',
                'cover_image_path' => UploadedFile::fake()->image('cover.jpg', 900, 600),
                'show_card_logo' => true,
                'is_published' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::query()->where('slug', 'automatic-card-image')->firstOrFail();

        $this->assertStringStartsWith('blog/cards/generated/', (string) $post->card_image_path);
        $this->assertTrue($post->show_card_logo);
        Storage::disk('public')->assertExists($post->card_image_path);
        $this->assertSame(
            [1200, 675],
            array_slice(getimagesize(Storage::disk('public')->path($post->card_image_path)), 0, 2),
        );
    }

    public function test_admin_can_change_article_heading_and_save(): void
    {
        $this->actingAs(User::factory()->create());

        $post = BlogPost::query()->create([
            'title' => 'Практический обзор',
            'slug' => 'practical-review',
            'body' => '<h1>Небольшой ликбез</h1><div class="lead"><p>Вводный текст</p></div><h2>Подраздел</h2><p>Текст статьи</p>',
            'tags' => ['СОРМ', 'ЛогистРу'],
            'is_published' => true,
            'published_at' => now(),
        ]);

        Livewire::test(EditBlogPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'body' => '<h2>Небольшой ликбез</h2><div class="lead"><p>Вводный текст</p></div><h3>Подраздел</h3><h4>Детали</h4><h5>Примечание</h5><h6>Справка</h6><p>Текст статьи</p>',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            '<h2>Небольшой ликбез</h2><div class="lead"><p>Вводный текст</p></div><h3>Подраздел</h3><h4>Детали</h4><h5>Примечание</h5><h6>Справка</h6><p>Текст статьи</p>',
            $post->fresh()->body,
        );
    }

    public function test_admin_can_save_deeply_nested_rich_editor_table_content(): void
    {
        $this->actingAs(User::factory()->create());

        $post = BlogPost::query()->create([
            'title' => 'Статья с таблицей',
            'slug' => 'article-with-table',
            'body' => '<p>Первый блок</p><p>Второй блок</p><table><tbody><tr><td><p>Исходный текст</p></td></tr></tbody></table>',
            'is_published' => false,
        ]);

        Livewire::test(EditBlogPost::class, ['record' => $post->getRouteKey()])
            ->set('data.body.content.2.content.0.content.0.content.0.content.0.text', 'Изменённый текст')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertStringContainsString('Изменённый текст', $post->fresh()->body);
    }

    public function test_admin_can_save_and_render_a_custom_font_size(): void
    {
        $this->actingAs(User::factory()->create());

        $post = BlogPost::query()->create([
            'title' => 'Статья с размером шрифта',
            'slug' => 'article-with-font-size',
            'body' => '<p>Исходный текст</p>',
            'is_published' => false,
        ]);

        $body = '<p><span class="font-size" data-font-size="24">Крупный текст</span></p>';

        Livewire::test(EditBlogPost::class, ['record' => $post->getRouteKey()])
            ->fillForm(['body' => $body])
            ->call('save')
            ->assertHasNoFormErrors();

        $savedBody = $post->fresh()->body;

        $this->assertStringContainsString('font-size-24', $savedBody);
        $this->assertStringContainsString('data-font-size="24"', $savedBody);
        $this->assertStringContainsString('font-size-24', $post->fresh()->renderBody());
    }

    public function test_article_body_image_is_rendered_from_public_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('blog/body/article-image.jpg', 'image');

        $post = new BlogPost([
            'body' => '<p><img data-id="blog/body/article-image.jpg" alt="Изображение статьи"></p>',
        ]);

        $renderedBody = $post->renderBody();

        $this->assertStringContainsString('src="/storage/blog/body/article-image.jpg"', $renderedBody);
        $this->assertStringContainsString('alt="Изображение статьи"', $renderedBody);
    }
}
