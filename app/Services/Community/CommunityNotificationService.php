<?php

namespace App\Services\Community;

use App\Jobs\DeliverCommunityNotification;
use App\Models\CommunityIdentity;
use App\Models\CommunityNotification;
use App\Models\CommunityUser;
use Illuminate\Database\UniqueConstraintViolationException;

final class CommunityNotificationService
{
    /** @param array<string, mixed> $data */
    public function create(
        CommunityUser $recipient,
        CommunityUser $actor,
        string $type,
        string $targetType,
        int $targetId,
        array $data,
    ): ?CommunityNotification {
        if ($recipient->is($actor)) {
            return null;
        }

        try {
            $notification = CommunityNotification::query()->create([
                'community_user_id' => $recipient->id,
                'actor_id' => $actor->id,
                'type' => $type,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'data' => $data,
            ]);
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        $recipient->identities()
            ->where('notifications_enabled', true)
            ->where('bot_access', true)
            ->where('bot_status', 'active')
            ->each(function (CommunityIdentity $identity) use ($notification): void {
                $delivery = $notification->deliveries()->create([
                    'community_identity_id' => $identity->id,
                    'provider' => $identity->provider,
                ]);
                DeliverCommunityNotification::dispatch($delivery->id);
            });

        return $notification;
    }
}
