<?php

namespace Tests\Feature;

use App\Filament\Clusters\Landing\Resources\BlogPosts\Pages\EditBlogPost;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BlogEditorTest extends TestCase
{
    use RefreshDatabase;

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
}
