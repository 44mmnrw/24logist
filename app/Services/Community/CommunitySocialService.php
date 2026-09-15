<?php

namespace App\Services\Community;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class CommunitySocialService
{
    public const MAX_REACTIONS_PER_TARGET = 3;

    public const REACTIONS = [
        'useful' => ['emoji' => '👍', 'label' => 'Полезно'],
        'thanks' => ['emoji' => '🙏', 'label' => 'Спасибо'],
        'same' => ['emoji' => '🤝', 'label' => 'Тоже сталкивался'],
        'frustrated' => ['emoji' => '😣', 'label' => 'Досадно'],
    ];

    public const AWARDS = [
        'applause' => ['emoji' => '👏', 'label' => 'Аплодисменты'],
        'heart' => ['emoji' => '💖', 'label' => 'От души'],
        'idea' => ['emoji' => '💡', 'label' => 'Отличная идея'],
        'gold' => ['emoji' => '🏆', 'label' => 'Золотой ответ'],
        'fire' => ['emoji' => '🔥', 'label' => 'Огонь'],
        'gem' => ['emoji' => '💎', 'label' => 'На вес золота'],
        'rocket' => ['emoji' => '🚀', 'label' => 'Продвигает дело'],
        'truck' => ['emoji' => '🚚', 'label' => 'Знаток логистики'],
    ];

    public function target(string $type, int $id): Model
    {
        $target = match ($type) {
            'post' => CommunityPost::query()->whereKey($id)->where('status', 'published')->first(),
            'comment' => CommunityComment::query()->with('post')->whereKey($id)->where('status', 'published')->first(),
            default => null,
        };

        abort_unless($target && ($type !== 'comment' || ($target->post?->status === 'published' && $target->post?->deleted_at === null)), 404);

        return $target;
    }

    /** @return array<int, array{reactions: array<string, int>, awards: array<string, int>, selected: array<int, string>, awarded: ?string}> */
    public function summaries(string $type, array $ids, ?int $viewerId): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $summaries = [];
        foreach ($ids as $id) {
            $summaries[$id] = ['reactions' => [], 'awards' => [], 'selected' => [], 'awarded' => null];
        }
        if ($ids === []) {
            return $summaries;
        }

        foreach (['community_reactions' => 'reactions', 'community_awards' => 'awards'] as $table => $key) {
            DB::table($table)->where('target_type', $type)->whereIn('target_id', $ids)
                ->select('target_id', 'code')->selectRaw('COUNT(*) AS total')
                ->groupBy('target_id', 'code')->get()
                ->each(function ($row) use (&$summaries, $key): void {
                    $summaries[$row->target_id][$key][$row->code] = (int) $row->total;
                });
        }

        if ($viewerId !== null) {
            DB::table('community_reactions')->where('community_user_id', $viewerId)
                ->where('target_type', $type)->whereIn('target_id', $ids)
                ->orderBy('id')->get(['target_id', 'code'])->each(function ($reaction) use (&$summaries): void {
                    $summaries[$reaction->target_id]['selected'][] = $reaction->code;
                });

            DB::table('community_awards')->where('community_user_id', $viewerId)
                ->where('target_type', $type)->whereIn('target_id', $ids)
                ->pluck('code', 'target_id')->each(function ($code, $id) use (&$summaries): void {
                    $summaries[$id]['awarded'] = $code;
                });
        }

        return $summaries;
    }

    /** @return array{reactions: array<string, int>, awards: array<string, int>, selected: array<int, string>, awarded: ?string} */
    public function summary(string $type, int $id, ?int $viewerId): array
    {
        return $this->summaries($type, [$id], $viewerId)[$id];
    }

    public function react(CommunityUser $user, string $type, int $id, string $code): array
    {
        $target = $this->target($type, $id);
        abort_if((int) $target->community_user_id === (int) $user->id, 403);

        DB::transaction(function () use ($user, $type, $id, $code): void {
            // Lock the target so concurrent clicks cannot exceed the per-target limit.
            $query = $type === 'post' ? CommunityPost::query() : CommunityComment::query();
            $locked = $query->whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'published', 404);
            if ($type === 'comment') {
                abort_unless(CommunityPost::query()->whereKey($locked->community_post_id)->where('status', 'published')->exists(), 404);
            }
            $reactions = DB::table('community_reactions')
                ->where('community_user_id', $user->id)->where('target_type', $type)->where('target_id', $id);
            $current = (clone $reactions)->where('code', $code)->first();
            if ($current) {
                DB::table('community_reactions')->where('id', $current->id)->delete();
            } else {
                abort_if((clone $reactions)->count() >= self::MAX_REACTIONS_PER_TARGET, 422, 'Можно выбрать не более трёх реакций.');
                DB::table('community_reactions')->insert([
                    'community_user_id' => $user->id, 'target_type' => $type, 'target_id' => $id,
                    'code' => $code, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        return $this->summary($type, $id, $user->id);
    }

    public function award(CommunityUser $user, string $type, int $id, string $code, ?string $message, bool $anonymous): ?int
    {
        $target = $this->target($type, $id);
        abort_if($target->community_user_id === null || (int) $target->community_user_id === (int) $user->id, 403);

        return DB::transaction(function () use ($user, $type, $id, $code, $message, $anonymous): ?int {
            $query = $type === 'post' ? CommunityPost::query() : CommunityComment::query();
            $locked = $query->whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'published', 404);
            if ($type === 'comment') {
                abort_unless(CommunityPost::query()->whereKey($locked->community_post_id)->where('status', 'published')->exists(), 404);
            }
            $exists = DB::table('community_awards')->where('community_user_id', $user->id)
                ->where('target_type', $type)->where('target_id', $id)->exists();
            if ($exists) {
                return null;
            }

            return DB::table('community_awards')->insertGetId([
                'community_user_id' => $user->id, 'target_type' => $type, 'target_id' => $id,
                'code' => $code, 'message' => $message, 'is_anonymous' => $anonymous,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function removeAward(CommunityUser $user, string $type, int $id): bool
    {
        $this->target($type, $id);

        return DB::transaction(function () use ($user, $type, $id): bool {
            $award = DB::table('community_awards')
                ->where('community_user_id', $user->id)
                ->where('target_type', $type)
                ->where('target_id', $id)
                ->lockForUpdate()
                ->first();

            if (! $award) {
                return false;
            }

            DB::table('community_notifications')
                ->where('target_type', 'award')
                ->where('target_id', $award->id)
                ->delete();
            DB::table('community_awards')->where('id', $award->id)->delete();

            return true;
        });
    }
}
