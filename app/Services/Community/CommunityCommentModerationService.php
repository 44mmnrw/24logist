<?php

namespace App\Services\Community;

use App\Models\CommunityComment;
use App\Models\CommunityModerationAction;
use App\Models\CommunityNotification;
use App\Models\CommunityPhoto;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CommunityCommentModerationService
{
    public const ACTION_APPROVE = 'approve';

    public const ACTION_HIDE = 'hide';

    public const ACTION_DELETE = 'delete';

    public const VIOLATION_LABELS = [
        'spam' => 'Спам или реклама',
        'harassment' => 'Оскорбления или травля',
        'hate' => 'Язык вражды',
        'threats' => 'Угрозы или призывы к насилию',
        'illegal' => 'Незаконный контент',
        'personal_data' => 'Персональные данные',
        'misinformation' => 'Опасная дезинформация',
        'off_topic' => 'Оффтоп или флуд',
        'duplicate' => 'Дубликат',
        'other' => 'Другое нарушение',
    ];

    public function __construct(
        private readonly CommunityCommentCounter $counter,
        private readonly CommunityNotificationService $notifications,
        private readonly CommunityPhotoService $photos,
        private readonly CommunityKarmaService $karma,
    ) {}

    /**
     * @return array{comment: CommunityComment, moderation_action: CommunityModerationAction, changed: bool, affected_comment_ids: list<int>}
     */
    public function moderate(
        CommunityComment $comment,
        string $action,
        ?int $adminUserId,
        ?string $violationCode = null,
        ?string $reason = null,
    ): array {
        if (! in_array($action, self::actions(), true)) {
            throw new InvalidArgumentException('Unsupported comment moderation action.');
        }

        $reason = trim((string) $reason);
        if (in_array($action, [self::ACTION_HIDE, self::ACTION_DELETE], true)
            && ! array_key_exists((string) $violationCode, self::VIOLATION_LABELS)) {
            throw new InvalidArgumentException('A valid violation category is required.');
        }

        $result = DB::transaction(function () use ($comment, $action, $adminUserId, $violationCode, $reason): array {
            $locked = CommunityComment::withTrashed()
                ->with(['post', 'author'])
                ->lockForUpdate()
                ->findOrFail($comment->getKey());
            $post = CommunityPost::withTrashed()->lockForUpdate()->findOrFail($locked->community_post_id);
            $before = $this->snapshot($locked);
            $wasTrashed = $locked->trashed();

            if ($action === self::ACTION_HIDE && ($wasTrashed || $locked->status !== CommunityComment::STATUS_PUBLISHED)) {
                throw new InvalidArgumentException('Only a published comment can be hidden.');
            }
            if ($action === self::ACTION_DELETE && ($wasTrashed || $locked->status === CommunityComment::STATUS_DELETED)) {
                throw new InvalidArgumentException('The comment is already deleted.');
            }

            $affectedIds = [$locked->id];
            $deletionBatch = null;
            if ($action === self::ACTION_APPROVE) {
                $affectedIds = $this->restore($locked);
            } elseif ($action === self::ACTION_HIDE) {
                $locked->update(['status' => CommunityComment::STATUS_HIDDEN]);
            } else {
                $affectedIds = $this->subtreeIds($locked);
                $deletionBatch = now()->startOfSecond();
                CommunityComment::withTrashed()
                    ->whereIn('id', $affectedIds)
                    ->update([
                        'status' => CommunityComment::STATUS_DELETED,
                        'deleted_at' => $deletionBatch,
                        'updated_at' => $deletionBatch,
                    ]);
                $locked->refresh();
            }

            if ($post->accepted_comment_id !== null && in_array($post->accepted_comment_id, $affectedIds, true)) {
                $post->update(['accepted_comment_id' => null, 'resolved_at' => null]);
            }
            $this->counter->sync($post);

            $reportsStatus = $action === self::ACTION_APPROVE ? 'dismissed' : 'actioned';
            $reportTargetIds = $action === self::ACTION_DELETE ? $affectedIds : [$locked->id];
            CommunityReport::query()
                ->where('target_type', 'comment')
                ->whereIn('target_id', $reportTargetIds)
                ->where('status', 'open')
                ->update(['status' => $reportsStatus, 'resolved_at' => now()]);

            $after = $this->snapshot($locked);
            $changed = $before !== $after;
            $moderationAction = CommunityModerationAction::query()->create([
                'admin_user_id' => $adminUserId,
                'target_type' => 'comment',
                'target_id' => $locked->id,
                'action' => $action,
                'reason' => $reason !== '' ? $reason : null,
                'metadata' => [
                    'violation_code' => $violationCode,
                    'violation_label' => self::VIOLATION_LABELS[$violationCode] ?? null,
                    'affected_comment_ids' => $affectedIds,
                    'deletion_batch' => $deletionBatch?->format('Y-m-d H:i:s'),
                    'before' => $before,
                    'after' => $after,
                    'changed' => $changed,
                ],
            ]);

            return [
                'comment' => $locked,
                'moderation_action' => $moderationAction,
                'changed' => $changed,
                'affected_comment_ids' => $affectedIds,
            ];
        });

        $postAuthorId = CommunityPost::withTrashed()
            ->whereKey($result['comment']->community_post_id)
            ->value('community_user_id');
        $affectedAuthorIds = CommunityComment::withTrashed()
            ->whereIn('id', $result['affected_comment_ids'])
            ->pluck('community_user_id')
            ->push($postAuthorId)
            ->all();
        $this->karma->recalculateMany($affectedAuthorIds);

        $this->notifyAuthor($result['comment'], $result['moderation_action'], $action, $violationCode, $reason);
        if (in_array($action, [self::ACTION_APPROVE, self::ACTION_DELETE], true)) {
            CommunityComment::withTrashed()
                ->with('author')
                ->whereIn('id', $result['affected_comment_ids'])
                ->whereKeyNot($result['comment']->id)
                ->each(function (CommunityComment $affected) use ($result, $action, $violationCode, $reason): void {
                    $this->notifyAuthor(
                        $affected,
                        $result['moderation_action'],
                        $action,
                        $violationCode,
                        $reason,
                        false,
                    );
                });
        }

        return $result;
    }

    /** @return list<string> */
    public static function actions(): array
    {
        return [self::ACTION_APPROVE, self::ACTION_HIDE, self::ACTION_DELETE];
    }

    /**
     * Permanently removes a previously soft-deleted comment branch.
     *
     * @return array{moderation_action: CommunityModerationAction, deleted_comment_ids: list<int>, deleted_count: int}
     */
    public function purge(CommunityComment $comment, ?int $adminUserId, ?string $reason = null): array
    {
        $photoRecords = collect();
        $karmaUserIds = [];

        $result = DB::transaction(function () use ($comment, $adminUserId, $reason, &$photoRecords, &$karmaUserIds): array {
            $locked = CommunityComment::withTrashed()
                ->lockForUpdate()
                ->findOrFail($comment->getKey());

            if (! $locked->trashed() || $locked->status !== CommunityComment::STATUS_DELETED) {
                throw new InvalidArgumentException('Only a deleted comment can be permanently deleted.');
            }

            $post = CommunityPost::withTrashed()->lockForUpdate()->findOrFail($locked->community_post_id);
            $affectedIds = $this->subtreeIds($locked);
            $branch = CommunityComment::withTrashed()
                ->whereIn('id', $affectedIds)
                ->lockForUpdate()
                ->get();
            $karmaUserIds = $branch->pluck('community_user_id')->push($post->community_user_id)->all();

            if ($branch->contains(fn (CommunityComment $item): bool => ! $item->trashed() || $item->status !== CommunityComment::STATUS_DELETED)) {
                throw new InvalidArgumentException('The branch contains restored comments and cannot be permanently deleted.');
            }

            $photoRecords = CommunityPhoto::query()
                ->whereIn('community_comment_id', $affectedIds)
                ->get();

            CommunityReport::query()
                ->where('target_type', 'comment')
                ->whereIn('target_id', $affectedIds)
                ->delete();
            CommunityNotification::query()
                ->where('target_type', 'comment')
                ->whereIn('target_id', $affectedIds)
                ->delete();
            DB::table('community_reactions')
                ->where('target_type', 'comment')
                ->whereIn('target_id', $affectedIds)
                ->delete();
            DB::table('community_awards')
                ->where('target_type', 'comment')
                ->whereIn('target_id', $affectedIds)
                ->delete();

            $moderationAction = CommunityModerationAction::query()->create([
                'admin_user_id' => $adminUserId,
                'target_type' => 'comment',
                'target_id' => $locked->id,
                'action' => 'force_delete',
                'reason' => filled($reason) ? trim((string) $reason) : null,
                'metadata' => [
                    'permanently_deleted' => true,
                    'deleted_comment_ids' => $affectedIds,
                    'deleted_count' => count($affectedIds),
                    'before' => $this->snapshot($locked),
                ],
            ]);

            $branch->sortByDesc('depth')->each->forceDelete();
            $this->counter->sync($post);

            return [
                'moderation_action' => $moderationAction,
                'deleted_comment_ids' => $affectedIds,
                'deleted_count' => count($affectedIds),
            ];
        });

        $this->karma->recalculateMany($karmaUserIds);

        $this->photos->deleteFiles($photoRecords);

        return $result;
    }

    /** @return list<int> */
    private function restore(CommunityComment $comment): array
    {
        if (! $comment->trashed() && $comment->status === CommunityComment::STATUS_PUBLISHED) {
            return [$comment->id];
        }

        $deleteAction = CommunityModerationAction::query()
            ->where('target_type', 'comment')
            ->where('target_id', $comment->id)
            ->where('action', self::ACTION_DELETE)
            ->latest('id')
            ->first();
        $affectedIds = array_values(array_filter(
            (array) data_get($deleteAction?->metadata, 'affected_comment_ids', [$comment->id]),
            fn ($id): bool => is_numeric($id),
        ));
        $deletionBatch = data_get($deleteAction?->metadata, 'deletion_batch');
        $query = CommunityComment::withTrashed()->whereIn('id', $affectedIds ?: [$comment->id]);
        if (is_string($deletionBatch) && $deletionBatch !== '') {
            $query->where('deleted_at', '=', $deletionBatch);
        } else {
            $query->whereKey($comment->id);
        }
        $restoredIds = (clone $query)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $query->update([
            'status' => CommunityComment::STATUS_PUBLISHED,
            'deleted_at' => null,
            'updated_at' => now(),
        ]);
        $comment->refresh();

        return $restoredIds ?: [$comment->id];
    }

    /** @return list<int> */
    private function subtreeIds(CommunityComment $comment): array
    {
        $ids = [$comment->id];
        $parents = [$comment->id];

        while ($parents !== []) {
            $children = CommunityComment::withTrashed()
                ->where('community_post_id', $comment->community_post_id)
                ->whereIn('parent_id', $parents)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $parents = array_values(array_diff($children, $ids));
            $ids = array_values(array_unique(array_merge($ids, $parents)));
        }

        return $ids;
    }

    /** @return array<string, mixed> */
    private function snapshot(CommunityComment $comment): array
    {
        return [
            'status' => $comment->status,
            'deleted_at' => $comment->deleted_at?->toIso8601String(),
            'post_id' => $comment->community_post_id,
            'author_id' => $comment->community_user_id,
            'parent_id' => $comment->parent_id,
        ];
    }

    private function notifyAuthor(
        CommunityComment $comment,
        CommunityModerationAction $moderationAction,
        string $action,
        ?string $violationCode,
        string $reason,
        bool $isDirectTarget = true,
    ): void {
        $author = $comment->author;
        if ($author === null || $author->trashed()) {
            return;
        }

        $violation = self::VIOLATION_LABELS[$violationCode] ?? null;
        $details = implode('. ', array_filter([$violation, $reason]));
        $message = match ($action) {
            self::ACTION_HIDE => 'Ваш комментарий скрыт модератором'.($details !== '' ? '. Причина: '.$details : '').'.',
            self::ACTION_DELETE => ($isDirectTarget ? 'Ваш комментарий удалён модератором' : 'Ваш ответ удалён вместе с веткой комментариев').($details !== '' ? '. Основание: '.$details : '').'.',
            self::ACTION_APPROVE => $isDirectTarget ? 'Ваш комментарий восстановлен модератором.' : 'Ваш ответ восстановлен вместе с веткой комментариев.',
        };

        $this->notifications->createSystem(
            $author,
            'moderation',
            'moderation_action',
            $moderationAction->getKey(),
            ['message' => $message, 'url' => route('community.notifications')],
        );
    }
}
