<?php

namespace Tests\Feature;

use App\Filament\Resources\CommunityComments\CommunityCommentResource;
use App\Filament\Resources\CommunityComments\Pages\EditCommunityComment;
use App\Filament\Resources\CommunityComments\Pages\ListCommunityComments;
use App\Filament\Resources\CommunityComments\Pages\ViewCommunityComment;
use App\Models\CommunityCategory;
use App\Models\CommunityComment;
use App\Models\CommunityModerationAction;
use App\Models\CommunityNotification;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Models\CommunityUser;
use App\Models\User;
use App\Services\Community\CommunityCommentModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class CommunityCommentModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_hide_and_approve_a_reported_comment_with_audit_and_notification(): void
    {
        $admin = User::factory()->create();
        [$post, $comment] = $this->makePostWithComment();
        $report = CommunityReport::query()->create([
            'community_user_id' => CommunityUser::factory()->create()->id,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'reason' => 'spam',
            'status' => 'open',
        ]);

        app(CommunityCommentModerationService::class)->moderate(
            $comment,
            CommunityCommentModerationService::ACTION_HIDE,
            $admin->id,
            'spam',
            'Рекламная рассылка.',
        );

        $this->assertSame(CommunityComment::STATUS_HIDDEN, $comment->fresh()->status);
        $this->assertSame(0, $post->fresh()->comments_count);
        $this->assertSame('actioned', $report->fresh()->status);
        $this->assertNotNull($report->fresh()->resolved_at);
        $this->assertDatabaseHas('community_moderation_actions', [
            'admin_user_id' => $admin->id,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'action' => 'hide',
            'reason' => 'Рекламная рассылка.',
        ]);
        $this->assertSame(
            'spam',
            CommunityModerationAction::query()->latest('id')->firstOrFail()->metadata['violation_code'],
        );
        $this->assertSame(1, $comment->author->communityNotifications()->count());

        $secondReport = CommunityReport::query()->create([
            'community_user_id' => null,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'reason' => 'other',
            'status' => 'open',
        ]);

        app(CommunityCommentModerationService::class)->moderate(
            $comment,
            CommunityCommentModerationService::ACTION_APPROVE,
            $admin->id,
            reason: 'Нарушение не подтвердилось.',
        );

        $this->assertSame(CommunityComment::STATUS_PUBLISHED, $comment->fresh()->status);
        $this->assertSame(1, $post->fresh()->comments_count);
        $this->assertSame('dismissed', $secondReport->fresh()->status);
        $this->assertSame(2, $comment->author->communityNotifications()->count());
    }

    public function test_delete_removes_the_whole_reply_branch_and_approve_restores_it(): void
    {
        $admin = User::factory()->create();
        [$post, $root] = $this->makePostWithComment();
        $child = $this->makeComment($post, $root, 'Ответ на комментарий');
        $grandchild = $this->makeComment($post, $child, 'Ответ второго уровня');
        $childReport = CommunityReport::query()->create([
            'community_user_id' => null,
            'target_type' => 'comment',
            'target_id' => $child->id,
            'reason' => 'abuse',
            'status' => 'open',
        ]);
        $post->update([
            'comments_count' => 3,
            'accepted_comment_id' => $child->id,
            'resolved_at' => now(),
        ]);

        app(CommunityCommentModerationService::class)->moderate(
            $root,
            CommunityCommentModerationService::ACTION_DELETE,
            $admin->id,
            'harassment',
            'Ветка содержит оскорбления.',
        );

        foreach ([$root, $child, $grandchild] as $deletedComment) {
            $this->assertSoftDeleted('community_comments', ['id' => $deletedComment->id]);
            $this->assertSame(
                CommunityComment::STATUS_DELETED,
                CommunityComment::withTrashed()->findOrFail($deletedComment->id)->status,
            );
        }
        $this->assertSame(0, $post->fresh()->comments_count);
        $this->assertNull($post->fresh()->accepted_comment_id);
        $this->assertNull($post->fresh()->resolved_at);
        $this->assertSame('actioned', $childReport->fresh()->status);
        $this->assertSame(1, $child->author->communityNotifications()->count());
        $this->assertSame(1, $grandchild->author->communityNotifications()->count());

        app(CommunityCommentModerationService::class)->moderate(
            CommunityComment::withTrashed()->findOrFail($root->id),
            CommunityCommentModerationService::ACTION_APPROVE,
            $admin->id,
            reason: 'Ветка восстановлена после пересмотра.',
        );

        foreach ([$root, $child, $grandchild] as $restoredComment) {
            $this->assertNotSoftDeleted('community_comments', ['id' => $restoredComment->id]);
            $this->assertSame(CommunityComment::STATUS_PUBLISHED, $restoredComment->fresh()->status);
        }
        $this->assertSame(3, $post->fresh()->comments_count);
        $this->assertSame(2, $child->author->communityNotifications()->count());
        $this->assertSame(2, $grandchild->author->communityNotifications()->count());
    }

    public function test_hiding_and_deleting_require_a_standard_violation_category(): void
    {
        [, $comment] = $this->makePostWithComment();

        $this->expectException(InvalidArgumentException::class);

        app(CommunityCommentModerationService::class)->moderate(
            $comment,
            CommunityCommentModerationService::ACTION_HIDE,
            User::factory()->create()->id,
            'unknown-rule',
        );
    }

    public function test_admin_comment_list_view_and_edit_pages_render_with_reports_and_history(): void
    {
        $admin = User::factory()->create();
        [, $comment] = $this->makePostWithComment();
        CommunityReport::query()->create([
            'community_user_id' => null,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'reason' => 'other',
            'details' => 'Нужна проверка модератора',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->get(CommunityCommentResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText('Модерация Комментариев Сообщества');

        $this->get(CommunityCommentResource::getUrl('view', ['record' => $comment]))
            ->assertOk()
            ->assertSeeText('Жалобы на комментарий')
            ->assertSeeText('История модерации');

        $this->get(CommunityCommentResource::getUrl('edit', ['record' => $comment]))
            ->assertOk()
            ->assertSeeText('Содержание комментария');
    }

    public function test_admin_comment_pages_render_when_the_parent_post_is_soft_deleted(): void
    {
        $admin = User::factory()->create();
        [$post, $comment] = $this->makePostWithComment();
        $post->delete();

        $this->actingAs($admin)
            ->get(CommunityCommentResource::getUrl('index'))
            ->assertOk();

        $this->get(CommunityCommentResource::getUrl('view', ['record' => $comment]))
            ->assertOk();

        $this->assertTrue($comment->fresh()->post->trashed());
    }

    public function test_admin_edit_rerenders_comment_markdown_and_is_audited(): void
    {
        $admin = User::factory()->create();
        [, $comment] = $this->makePostWithComment();
        $this->actingAs($admin);

        Livewire::test(EditCommunityComment::class, ['record' => $comment->getRouteKey()])
            ->fillForm(['body_markdown' => '**Проверенный текст**'])
            ->call('save')
            ->assertHasNoFormErrors();

        $comment->refresh();
        $this->assertSame('**Проверенный текст**', $comment->body_markdown);
        $this->assertSame('<p><strong>Проверенный текст</strong></p>', $comment->body_html);
        $this->assertNotNull($comment->edited_at);
        $this->assertDatabaseHas('community_moderation_actions', [
            'admin_user_id' => $admin->id,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'action' => 'admin_edit',
        ]);
    }

    public function test_admin_can_hide_a_comment_and_suspend_its_author_from_the_view_page(): void
    {
        $admin = User::factory()->create();
        [, $comment] = $this->makePostWithComment();
        $this->actingAs($admin);

        Livewire::test(ViewCommunityComment::class, ['record' => $comment->getRouteKey()])
            ->callAction('hide_comment', [
                'violation' => 'harassment',
                'reason' => 'Оскорбление участника.',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertSame(CommunityComment::STATUS_HIDDEN, $comment->fresh()->status);

        Livewire::test(ViewCommunityComment::class, ['record' => $comment->getRouteKey()])
            ->callAction('suspend_author', [
                'duration_days' => 7,
                'reason' => 'Повторное нарушение правил общения.',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertTrue($comment->author->fresh()->isRestricted());
        $this->assertDatabaseHas('community_moderation_actions', [
            'admin_user_id' => $admin->id,
            'target_type' => 'user',
            'target_id' => $comment->community_user_id,
            'action' => 'suspend',
        ]);
    }

    public function test_admin_can_bulk_delete_overlapping_reply_branches_safely(): void
    {
        $admin = User::factory()->create();
        [$post, $root] = $this->makePostWithComment();
        $child = $this->makeComment($post, $root, 'Ответ для пакетной модерации');
        $post->update(['comments_count' => 2]);
        $this->actingAs($admin);

        Livewire::test(ListCommunityComments::class)
            ->callTableBulkAction('bulk_delete', [$root, $child], [
                'violation' => 'spam',
                'reason' => 'Пакетная очистка спама.',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $this->assertSoftDeleted('community_comments', ['id' => $root->id]);
        $this->assertSoftDeleted('community_comments', ['id' => $child->id]);
        $this->assertSame(0, $post->fresh()->comments_count);
        $this->assertSame(1, CommunityModerationAction::query()
            ->where('target_type', 'comment')
            ->where('action', 'delete')
            ->count());
    }

    public function test_permanent_deletion_removes_the_deleted_branch_and_all_attached_data(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create();
        [$post, $root] = $this->makePostWithComment();
        $child = $this->makeComment($post, $root, 'Ответ с вложениями');
        $post->update(['comments_count' => 2]);
        $photoPath = 'community/photos/permanent-delete.webp';
        Storage::disk('local')->put($photoPath, 'image');
        $photo = $child->photos()->create([
            'path' => $photoPath,
            'width' => 100,
            'height' => 100,
            'position' => 0,
        ]);
        $reactor = CommunityUser::factory()->create();
        DB::table('community_reactions')->insert([
            'community_user_id' => $reactor->id,
            'target_type' => 'comment',
            'target_id' => $child->id,
            'code' => 'useful',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('community_awards')->insert([
            'community_user_id' => $reactor->id,
            'target_type' => 'comment',
            'target_id' => $child->id,
            'code' => 'diamond',
            'is_anonymous' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $moderation = app(CommunityCommentModerationService::class);
        $moderation->moderate($root, CommunityCommentModerationService::ACTION_DELETE, $admin->id, 'spam', 'Спам-ветка.');
        $report = CommunityReport::query()->create([
            'community_user_id' => null,
            'target_type' => 'comment',
            'target_id' => $child->id,
            'reason' => 'spam',
            'status' => 'open',
        ]);
        $notification = CommunityNotification::query()->create([
            'community_user_id' => $reactor->id,
            'type' => 'reply',
            'target_type' => 'comment',
            'target_id' => $child->id,
            'data' => ['message' => 'Ответ'],
        ]);

        $result = $moderation->purge(
            CommunityComment::withTrashed()->findOrFail($root->id),
            $admin->id,
            'Срок хранения завершён.',
        );

        $this->assertSame(2, $result['deleted_count']);
        $this->assertNull(CommunityComment::withTrashed()->find($root->id));
        $this->assertNull(CommunityComment::withTrashed()->find($child->id));
        $this->assertDatabaseMissing('community_photos', ['id' => $photo->id]);
        Storage::disk('local')->assertMissing($photoPath);
        $this->assertDatabaseMissing('community_reports', ['id' => $report->id]);
        $this->assertDatabaseMissing('community_notifications', ['id' => $notification->id]);
        $this->assertDatabaseMissing('community_reactions', ['target_type' => 'comment', 'target_id' => $child->id]);
        $this->assertDatabaseMissing('community_awards', ['target_type' => 'comment', 'target_id' => $child->id]);
        $this->assertSame(0, $post->fresh()->comments_count);
        $this->assertDatabaseHas('community_moderation_actions', [
            'admin_user_id' => $admin->id,
            'target_type' => 'comment',
            'target_id' => $root->id,
            'action' => 'force_delete',
            'reason' => 'Срок хранения завершён.',
        ]);
    }

    public function test_admin_can_permanently_delete_an_already_deleted_comment_from_the_view_page(): void
    {
        $admin = User::factory()->create();
        [, $comment] = $this->makePostWithComment();
        app(CommunityCommentModerationService::class)->moderate(
            $comment,
            CommunityCommentModerationService::ACTION_DELETE,
            $admin->id,
            'other',
            'Удалено после проверки.',
        );
        $this->actingAs($admin);

        Livewire::test(ViewCommunityComment::class, ['record' => $comment->getRouteKey()])
            ->callAction('force_delete_comment', [
                'confirmation' => 'УДАЛИТЬ',
                'reason' => 'Окончательное решение модератора.',
            ])
            ->assertHasNoActionErrors()
            ->assertRedirect(CommunityCommentResource::getUrl('index'));

        $this->assertNull(CommunityComment::withTrashed()->find($comment->id));
    }

    /** @return array{CommunityPost, CommunityComment} */
    private function makePostWithComment(): array
    {
        $author = CommunityUser::factory()->create();
        $post = CommunityPost::query()->create([
            'community_user_id' => CommunityUser::factory()->create()->id,
            'community_category_id' => CommunityCategory::query()->firstOrFail()->id,
            'slug' => 'comment-moderation-'.uniqid(),
            'title' => 'Тема для модерации комментариев',
            'body_markdown' => 'Содержимое темы',
            'body_html' => '<p>Содержимое темы</p>',
            'status' => CommunityPost::STATUS_PUBLISHED,
            'comments_count' => 1,
            'published_at' => now(),
        ]);
        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => $author->id,
            'body_markdown' => 'Комментарий для проверки',
            'body_html' => '<p>Комментарий для проверки</p>',
            'status' => CommunityComment::STATUS_PUBLISHED,
            'score' => 1,
        ]);
        $comment->update(['root_id' => $comment->id]);

        return [$post, $comment];
    }

    private function makeComment(CommunityPost $post, CommunityComment $parent, string $body): CommunityComment
    {
        return CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => CommunityUser::factory()->create()->id,
            'parent_id' => $parent->id,
            'root_id' => $parent->root_id ?? $parent->id,
            'depth' => $parent->depth + 1,
            'body_markdown' => $body,
            'body_html' => '<p>'.$body.'</p>',
            'status' => CommunityComment::STATUS_PUBLISHED,
            'score' => 1,
        ]);
    }
}
