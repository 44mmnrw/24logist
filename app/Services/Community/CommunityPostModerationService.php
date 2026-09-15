<?php

namespace App\Services\Community;

use App\Models\CommunityModerationAction;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CommunityPostModerationService
{
    public const ACTION_APPROVE = 'approve';

    public const ACTION_HIDE = 'hide';

    public const ACTION_DELETE = 'delete';

    public const ACTION_LOCK = 'lock';

    public const ACTION_UNLOCK = 'unlock';

    public const ACTION_PIN = 'pin';

    public const ACTION_UNPIN = 'unpin';

    /**
     * @return array{post: CommunityPost, changed: bool}
     */
    public function moderate(CommunityPost $post, string $action, ?int $adminUserId, ?string $reason = null): array
    {
        if (! in_array($action, self::actions(), true)) {
            throw new InvalidArgumentException('Unsupported post moderation action.');
        }

        return DB::transaction(function () use ($post, $action, $adminUserId, $reason): array {
            $locked = CommunityPost::withTrashed()->lockForUpdate()->findOrFail($post->getKey());
            $before = $this->snapshot($locked);
            $wasTrashed = $locked->trashed();

            $attributes = match ($action) {
                self::ACTION_APPROVE => [
                    'status' => CommunityPost::STATUS_PUBLISHED,
                    'published_at' => $locked->published_at ?? now(),
                ],
                self::ACTION_HIDE => $locked->status === CommunityPost::STATUS_PUBLISHED
                    ? ['status' => CommunityPost::STATUS_HIDDEN, 'is_pinned' => false]
                    : [],
                self::ACTION_DELETE => $locked->status !== CommunityPost::STATUS_DELETED
                    ? [
                        'status' => CommunityPost::STATUS_DELETED,
                        'is_pinned' => false,
                        'locked_at' => $locked->locked_at ?? now(),
                    ]
                    : [],
                self::ACTION_LOCK => ['locked_at' => $locked->locked_at ?? now()],
                self::ACTION_UNLOCK => ['locked_at' => null],
                self::ACTION_PIN => ['is_pinned' => true],
                self::ACTION_UNPIN => ['is_pinned' => false],
            };

            if ($action === self::ACTION_PIN && $locked->status !== CommunityPost::STATUS_PUBLISHED) {
                throw new InvalidArgumentException('Only published posts can be pinned.');
            }

            $locked->fill($attributes);
            $changed = $locked->isDirty()
                || ($action === self::ACTION_DELETE && ! $wasTrashed)
                || ($action === self::ACTION_APPROVE && $wasTrashed);

            if ($locked->isDirty()) {
                $locked->save();
            }

            if ($action === self::ACTION_DELETE && ! $wasTrashed) {
                $locked->delete();
            } elseif ($action === self::ACTION_APPROVE && $wasTrashed) {
                $locked->restore();
            }

            $reportsStatus = match ($action) {
                self::ACTION_APPROVE => 'dismissed',
                self::ACTION_HIDE, self::ACTION_DELETE => 'actioned',
                default => null,
            };

            if ($reportsStatus !== null) {
                CommunityReport::query()
                    ->where('target_type', 'post')
                    ->where('target_id', $locked->getKey())
                    ->where('status', 'open')
                    ->update(['status' => $reportsStatus, 'resolved_at' => now()]);
            }

            CommunityModerationAction::query()->create([
                'admin_user_id' => $adminUserId,
                'target_type' => 'post',
                'target_id' => $locked->getKey(),
                'action' => $action,
                'reason' => filled($reason) ? trim((string) $reason) : null,
                'metadata' => [
                    'before' => $before,
                    'after' => $this->snapshot($locked),
                    'changed' => $changed,
                ],
            ]);

            return ['post' => $locked, 'changed' => $changed];
        });
    }

    /** @return list<string> */
    public static function actions(): array
    {
        return [
            self::ACTION_APPROVE,
            self::ACTION_HIDE,
            self::ACTION_DELETE,
            self::ACTION_LOCK,
            self::ACTION_UNLOCK,
            self::ACTION_PIN,
            self::ACTION_UNPIN,
        ];
    }

    /** @return array<string, mixed> */
    private function snapshot(CommunityPost $post): array
    {
        return [
            'status' => $post->status,
            'is_pinned' => (bool) $post->is_pinned,
            'locked_at' => $post->locked_at?->toIso8601String(),
            'published_at' => $post->published_at?->toIso8601String(),
            'deleted_at' => $post->deleted_at?->toIso8601String(),
        ];
    }
}
