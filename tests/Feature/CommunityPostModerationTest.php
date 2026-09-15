<?php

namespace Tests\Feature;

use App\Filament\Resources\CommunityPosts\CommunityPostResource;
use App\Filament\Resources\CommunityPosts\Pages\CreateCommunityPost;
use App\Filament\Resources\CommunityPosts\Pages\EditCommunityPost;
use App\Filament\Resources\CommunityPosts\Pages\ListCommunityPosts;
use App\Filament\Resources\CommunityPosts\Pages\ViewCommunityPost;
use App\Models\CommunityAiPersona;
use App\Models\CommunityCategory;
use App\Models\CommunityModerationAction;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunityUser;
use App\Models\User;
use App\Services\Community\CommunityPostModerationService;
use Database\Seeders\CommunityAiPersonaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class CommunityPostModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_hide_and_approve_a_reported_post_with_audit_history(): void
    {
        $admin = User::factory()->create();
        $post = $this->makePost(['is_pinned' => true]);
        $report = CommunityReport::query()->create([
            'community_user_id' => CommunityUser::factory()->create()->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'spam',
            'status' => 'open',
        ]);
        $commentReport = CommunityReport::query()->create([
            'community_user_id' => null,
            'target_type' => 'comment',
            'target_id' => $post->id,
            'reason' => 'other',
            'status' => 'open',
        ]);

        $result = app(CommunityPostModerationService::class)->moderate(
            $post,
            CommunityPostModerationService::ACTION_HIDE,
            $admin->id,
            'Реклама',
        );

        $this->assertTrue($result['changed']);
        $this->assertSame(CommunityPost::STATUS_HIDDEN, $post->fresh()->status);
        $this->assertFalse($post->fresh()->is_pinned);
        $this->assertSame('actioned', $report->fresh()->status);
        $this->assertNotNull($report->fresh()->resolved_at);
        $this->assertSame('open', $commentReport->fresh()->status);

        $hideAction = CommunityModerationAction::query()->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $hideAction->admin_user_id);
        $this->assertSame('hide', $hideAction->action);
        $this->assertSame('Реклама', $hideAction->reason);
        $this->assertSame('published', $hideAction->metadata['before']['status']);
        $this->assertSame('hidden', $hideAction->metadata['after']['status']);

        $secondReport = CommunityReport::query()->create([
            'community_user_id' => null,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'other',
            'status' => 'open',
        ]);

        app(CommunityPostModerationService::class)->moderate(
            $post,
            CommunityPostModerationService::ACTION_APPROVE,
            $admin->id,
            'Нарушение не подтвердилось',
        );

        $this->assertSame(CommunityPost::STATUS_PUBLISHED, $post->fresh()->status);
        $this->assertSame('dismissed', $secondReport->fresh()->status);
        $this->assertDatabaseHas('community_moderation_actions', [
            'admin_user_id' => $admin->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'action' => 'approve',
            'reason' => 'Нарушение не подтвердилось',
        ]);
    }

    public function test_admin_can_lock_pin_unpin_unlock_and_delete_a_post(): void
    {
        $admin = User::factory()->create();
        $post = $this->makePost();
        $moderation = app(CommunityPostModerationService::class);

        $moderation->moderate($post, CommunityPostModerationService::ACTION_LOCK, $admin->id);
        $this->assertNotNull($post->fresh()->locked_at);

        $moderation->moderate($post, CommunityPostModerationService::ACTION_PIN, $admin->id);
        $this->assertTrue($post->fresh()->is_pinned);

        $moderation->moderate($post, CommunityPostModerationService::ACTION_UNPIN, $admin->id);
        $this->assertFalse($post->fresh()->is_pinned);

        $moderation->moderate($post, CommunityPostModerationService::ACTION_UNLOCK, $admin->id);
        $this->assertNull($post->fresh()->locked_at);

        $moderation->moderate($post, CommunityPostModerationService::ACTION_DELETE, $admin->id, 'Грубое нарушение');
        $post = CommunityPost::withTrashed()->findOrFail($post->id);

        $this->assertSame(CommunityPost::STATUS_DELETED, $post->status);
        $this->assertTrue($post->trashed());
        $this->assertFalse($post->is_pinned);
        $this->assertNotNull($post->locked_at);
        $this->assertSame(5, $post->moderationActions()->count());

        $moderation->moderate($post, CommunityPostModerationService::ACTION_APPROVE, $admin->id, 'Восстановлено');

        $restoredPost = CommunityPost::query()->findOrFail($post->id);
        $this->assertFalse($restoredPost->trashed());
        $this->assertSame(CommunityPost::STATUS_PUBLISHED, $restoredPost->status);
    }

    public function test_hidden_post_cannot_be_pinned(): void
    {
        $post = $this->makePost(['status' => CommunityPost::STATUS_HIDDEN]);

        $this->expectException(InvalidArgumentException::class);

        app(CommunityPostModerationService::class)->moderate(
            $post,
            CommunityPostModerationService::ACTION_PIN,
            User::factory()->create()->id,
        );
    }

    public function test_admin_moderation_list_view_and_edit_pages_render(): void
    {
        $this->withoutVite();
        $admin = User::factory()->create();
        $post = $this->makePost();
        CommunityReport::query()->create([
            'community_user_id' => null,
            'target_type' => 'post',
            'target_id' => $post->id,
            'reason' => 'other',
            'details' => 'Нужна проверка модератора',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->get(CommunityPostResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Модерация тем');

        $this->get(CommunityPostResource::getUrl('view', ['record' => $post]))
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee('Жалобы на тему');

        $this->get(CommunityPostResource::getUrl('edit', ['record' => $post]))
            ->assertOk()
            ->assertSee('Содержание темы');
    }

    public function test_admin_can_publish_a_manual_topic_as_any_active_persona(): void
    {
        $this->seed(CommunityAiPersonaSeeder::class);
        $admin = User::factory()->create();
        $persona = CommunityAiPersona::query()->with('communityUser')->where('can_create_posts', true)->firstOrFail();
        $category = CommunityCategory::query()->firstOrFail();
        $publishedAt = now()->subDays(5)->setTime(14, 30)->setMicrosecond(0);
        $this->actingAs($admin);

        Livewire::test(CreateCommunityPost::class)
            ->fillForm([
                'community_user_id' => $persona->community_user_id,
                'community_category_id' => $category->id,
                'title' => 'Ручная публикация от персоны',
                'body_markdown' => "Первый абзац.\n\n**Второй абзац.**",
                'external_url' => null,
                'published_at' => $publishedAt,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $post = CommunityPost::query()->where('title', 'Ручная публикация от персоны')->firstOrFail();
        $this->assertSame($persona->community_user_id, $post->community_user_id);
        $this->assertSame(CommunityPost::STATUS_PUBLISHED, $post->status);
        $this->assertSame($publishedAt->toDateTimeString(), $post->published_at->toDateTimeString());
        $this->assertSame($publishedAt->toDateTimeString(), $post->created_at->toDateTimeString());
        $this->assertSame('<p>Первый абзац.</p>'."\n".'<p><strong>Второй абзац.</strong></p>', $post->body_html);
        $this->assertDatabaseHas('community_post_votes', [
            'community_user_id' => $persona->community_user_id,
            'community_post_id' => $post->id,
            'value' => 1,
        ]);
    }

    public function test_admin_edit_rerenders_markdown_and_is_audited(): void
    {
        $admin = User::factory()->create();
        $post = $this->makePost();
        $this->actingAs($admin);

        Livewire::test(EditCommunityPost::class, ['record' => $post->getRouteKey()])
            ->fillForm([
                'title' => 'Исправленный заголовок',
                'community_category_id' => $post->community_category_id,
                'body_markdown' => '**Проверенный текст**',
                'external_url' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();
        $this->assertSame('Исправленный заголовок', $post->title);
        $this->assertSame('<p><strong>Проверенный текст</strong></p>', $post->body_html);
        $this->assertDatabaseHas('community_moderation_actions', [
            'admin_user_id' => $admin->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'action' => 'admin_edit',
        ]);
    }

    public function test_admin_can_run_moderation_action_from_the_post_page(): void
    {
        $admin = User::factory()->create();
        $post = $this->makePost();
        $this->actingAs($admin);

        Livewire::test(ViewCommunityPost::class, ['record' => $post->getRouteKey()])
            ->callAction('hide_post', ['reason' => 'Проверено в админке'])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertSame(CommunityPost::STATUS_HIDDEN, $post->fresh()->status);
        $this->assertDatabaseHas('community_moderation_actions', [
            'admin_user_id' => $admin->id,
            'target_type' => 'post',
            'target_id' => $post->id,
            'action' => 'hide',
            'reason' => 'Проверено в админке',
        ]);
    }

    public function test_admin_delete_action_soft_deletes_the_post_and_redirects_to_the_list(): void
    {
        $admin = User::factory()->create();
        $post = $this->makePost();
        $this->actingAs($admin);

        Livewire::test(ViewCommunityPost::class, ['record' => $post->getRouteKey()])
            ->callAction('delete_post', ['reason' => 'Спам'])
            ->assertHasNoActionErrors()
            ->assertRedirect(CommunityPostResource::getUrl('index'));

        $this->assertSoftDeleted('community_posts', ['id' => $post->id]);
        $this->assertSame(
            CommunityPost::STATUS_DELETED,
            CommunityPost::withTrashed()->findOrFail($post->id)->status,
        );
    }

    public function test_admin_can_bulk_moderate_posts_without_resurrecting_deleted_topics(): void
    {
        $admin = User::factory()->create();
        $published = $this->makePost();
        $deleted = $this->makePost(['status' => CommunityPost::STATUS_DELETED]);
        $this->actingAs($admin);

        Livewire::test(ListCommunityPosts::class)
            ->callTableBulkAction('bulk_hide', [$published, $deleted], ['reason' => 'Пакетная проверка'])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertSame(CommunityPost::STATUS_HIDDEN, $published->fresh()->status);
        $this->assertSame(CommunityPost::STATUS_DELETED, $deleted->fresh()->status);
        $this->assertDatabaseCount('community_moderation_actions', 2);
    }

    /** @param array<string, mixed> $attributes */
    private function makePost(array $attributes = []): CommunityPost
    {
        $author = CommunityUser::factory()->create();

        return CommunityPost::query()->create(array_merge([
            'community_user_id' => $author->id,
            'community_category_id' => CommunityCategory::query()->firstOrFail()->id,
            'slug' => 'moderation-test-'.uniqid(),
            'title' => 'Тема для модерации',
            'body_markdown' => 'Содержимое темы',
            'body_html' => '<p>Содержимое темы</p>',
            'status' => CommunityPost::STATUS_PUBLISHED,
            'published_at' => now(),
        ], $attributes));
    }
}
