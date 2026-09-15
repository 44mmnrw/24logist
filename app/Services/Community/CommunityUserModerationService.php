<?php

namespace App\Services\Community;

use App\Models\CommunityModerationAction;
use App\Models\CommunityUser;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CommunityUserModerationService
{
    public const ACTION_WARN = 'warn';

    public const ACTION_SUSPEND = 'suspend';

    public const ACTION_BAN = 'ban';

    public const ACTION_UNRESTRICT = 'unrestrict';

    public const ACTION_DELETE = 'delete_user';

    public const ACTION_RESTORE = 'restore_user';

    public function __construct(private readonly CommunityNotificationService $notifications) {}

    /**
     * @return array{user: CommunityUser, moderation_action: CommunityModerationAction, changed: bool}
     */
    public function moderate(
        CommunityUser $user,
        string $action,
        ?int $adminUserId,
        ?string $reason = null,
        ?int $durationDays = null,
    ): array {
        if (! in_array($action, self::actions(), true)) {
            throw new InvalidArgumentException('Unsupported user moderation action.');
        }

        $reason = trim((string) $reason);
        if (in_array($action, [self::ACTION_WARN, self::ACTION_SUSPEND, self::ACTION_BAN, self::ACTION_DELETE], true) && $reason === '') {
            throw new InvalidArgumentException('A moderation reason is required.');
        }

        if ($action === self::ACTION_SUSPEND && ($durationDays === null || $durationDays < 1 || $durationDays > 365)) {
            throw new InvalidArgumentException('Suspension duration must be between 1 and 365 days.');
        }

        $result = DB::transaction(function () use ($user, $action, $adminUserId, $reason, $durationDays): array {
            $locked = CommunityUser::withTrashed()->lockForUpdate()->findOrFail($user->getKey());
            $before = $this->snapshot($locked);

            if ($locked->trashed() && $action !== self::ACTION_RESTORE) {
                throw new InvalidArgumentException('Deleted users can only be restored.');
            }

            if (! $locked->trashed() && $action === self::ACTION_RESTORE) {
                throw new InvalidArgumentException('The user is not deleted.');
            }

            if ($action === self::ACTION_SUSPEND && $locked->banned_at !== null) {
                throw new InvalidArgumentException('Permanently banned users cannot be temporarily suspended.');
            }

            match ($action) {
                self::ACTION_SUSPEND => $locked->fill([
                    'suspended_until' => now()->addDays((int) $durationDays),
                    'banned_at' => null,
                ])->save(),
                self::ACTION_BAN => $locked->fill([
                    'banned_at' => now(),
                    'suspended_until' => null,
                ])->save(),
                self::ACTION_UNRESTRICT => $locked->fill([
                    'banned_at' => null,
                    'suspended_until' => null,
                ])->save(),
                self::ACTION_DELETE => $locked->delete(),
                self::ACTION_RESTORE => $locked->restore(),
                self::ACTION_WARN => null,
            };

            $locked->refresh();
            $after = $this->snapshot($locked);
            $moderationAction = CommunityModerationAction::query()->create([
                'admin_user_id' => $adminUserId,
                'target_type' => 'user',
                'target_id' => $locked->getKey(),
                'action' => $action,
                'reason' => $reason !== '' ? $reason : null,
                'metadata' => [
                    'duration_days' => $durationDays,
                    'before' => $before,
                    'after' => $after,
                    'changed' => $before !== $after || $action === self::ACTION_WARN,
                ],
            ]);

            return [
                'user' => $locked,
                'moderation_action' => $moderationAction,
                'changed' => $before !== $after || $action === self::ACTION_WARN,
            ];
        });

        $this->notifyUser($result['user'], $result['moderation_action'], $action, $reason);

        return $result;
    }

    /** @return list<string> */
    public static function actions(): array
    {
        return [
            self::ACTION_WARN,
            self::ACTION_SUSPEND,
            self::ACTION_BAN,
            self::ACTION_UNRESTRICT,
            self::ACTION_DELETE,
            self::ACTION_RESTORE,
        ];
    }

    /** @return array<string, mixed> */
    private function snapshot(CommunityUser $user): array
    {
        return [
            'deleted_at' => $user->deleted_at?->toIso8601String(),
            'suspended_until' => $user->suspended_until?->toIso8601String(),
            'banned_at' => $user->banned_at?->toIso8601String(),
        ];
    }

    private function notifyUser(
        CommunityUser $user,
        CommunityModerationAction $moderationAction,
        string $action,
        string $reason,
    ): void {
        $message = match ($action) {
            self::ACTION_WARN => 'Предупреждение модератора: '.$reason,
            self::ACTION_SUSPEND => 'Ваше участие в сообществе ограничено до '.$user->suspended_until?->format('d.m.Y H:i').'. Причина: '.$reason,
            self::ACTION_BAN => 'Ваш аккаунт заблокирован. Причина: '.$reason,
            self::ACTION_UNRESTRICT => 'Ограничения с вашего аккаунта сняты.',
            self::ACTION_RESTORE => 'Ваш аккаунт сообщества восстановлен.',
            default => null,
        };

        if ($message === null) {
            return;
        }

        $this->notifications->createSystem(
            $user,
            'moderation',
            'moderation_action',
            $moderationAction->getKey(),
            [
                'message' => $message,
                'url' => route('community.notifications'),
            ],
        );
    }
}
