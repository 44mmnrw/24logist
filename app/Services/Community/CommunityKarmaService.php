<?php

namespace App\Services\Community;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityUser;
use Illuminate\Support\Facades\DB;

final class CommunityKarmaService
{
    public const COMMENT_RECEIVED = 1;

    public const ACCEPTED_ANSWER = 10;

    public const REACTION_WEIGHTS = [
        'useful' => 2,
        'thanks' => 1,
        'same' => 1,
        'frustrated' => 1,
    ];

    public const AWARD_WEIGHT = 5;

    public function recalculate(?int $userId): void
    {
        if ($userId === null || ! CommunityUser::query()->whereKey($userId)->exists()) {
            return;
        }

        CommunityUser::query()->whereKey($userId)->update([
            'karma' => $this->calculate($userId),
        ]);
    }

    /** @param iterable<int|null> $userIds */
    public function recalculateMany(iterable $userIds): void
    {
        collect($userIds)
            ->filter(fn ($id): bool => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->each(fn (int $userId) => $this->recalculate($userId));
    }

    public function calculate(int $userId): int
    {
        return $this->postVotes($userId)
            + $this->commentVotes($userId)
            + $this->socialPoints($userId, 'community_reactions', self::REACTION_WEIGHTS)
            + $this->socialPoints($userId, 'community_awards', array_fill_keys(array_keys(CommunitySocialService::AWARDS), self::AWARD_WEIGHT))
            + ($this->receivedComments($userId) * self::COMMENT_RECEIVED)
            + ($this->acceptedAnswers($userId) * self::ACCEPTED_ANSWER);
    }

    private function postVotes(int $userId): int
    {
        return (int) DB::table('community_post_votes as votes')
            ->join('community_posts as posts', 'posts.id', '=', 'votes.community_post_id')
            ->where('posts.community_user_id', $userId)
            ->where('posts.status', CommunityPost::STATUS_PUBLISHED)
            ->whereNull('posts.deleted_at')
            ->whereColumn('votes.community_user_id', '<>', 'posts.community_user_id')
            ->sum('votes.value');
    }

    private function commentVotes(int $userId): int
    {
        return (int) DB::table('community_comment_votes as votes')
            ->join('community_comments as comments', 'comments.id', '=', 'votes.community_comment_id')
            ->join('community_posts as posts', 'posts.id', '=', 'comments.community_post_id')
            ->where('comments.community_user_id', $userId)
            ->where('comments.status', CommunityComment::STATUS_PUBLISHED)
            ->whereNull('comments.deleted_at')
            ->where('posts.status', CommunityPost::STATUS_PUBLISHED)
            ->whereNull('posts.deleted_at')
            ->whereColumn('votes.community_user_id', '<>', 'comments.community_user_id')
            ->sum('votes.value');
    }

    /** @param array<string, int> $weights */
    private function socialPoints(int $userId, string $table, array $weights): int
    {
        $postCounts = DB::table($table.' as social')
            ->join('community_posts as targets', 'targets.id', '=', 'social.target_id')
            ->where('social.target_type', 'post')
            ->where('targets.community_user_id', $userId)
            ->where('targets.status', CommunityPost::STATUS_PUBLISHED)
            ->whereNull('targets.deleted_at')
            ->whereColumn('social.community_user_id', '<>', 'targets.community_user_id')
            ->select('social.code')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('social.code')
            ->pluck('total', 'code');

        $commentCounts = DB::table($table.' as social')
            ->join('community_comments as targets', 'targets.id', '=', 'social.target_id')
            ->join('community_posts as posts', 'posts.id', '=', 'targets.community_post_id')
            ->where('social.target_type', 'comment')
            ->where('targets.community_user_id', $userId)
            ->where('targets.status', CommunityComment::STATUS_PUBLISHED)
            ->whereNull('targets.deleted_at')
            ->where('posts.status', CommunityPost::STATUS_PUBLISHED)
            ->whereNull('posts.deleted_at')
            ->whereColumn('social.community_user_id', '<>', 'targets.community_user_id')
            ->select('social.code')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('social.code')
            ->pluck('total', 'code');

        return collect($weights)->sum(
            fn (int $weight, string $code): int => $weight * ((int) ($postCounts[$code] ?? 0) + (int) ($commentCounts[$code] ?? 0)),
        );
    }

    private function receivedComments(int $userId): int
    {
        return DB::table('community_comments as comments')
            ->join('community_posts as posts', 'posts.id', '=', 'comments.community_post_id')
            ->where('posts.community_user_id', $userId)
            ->where('posts.status', CommunityPost::STATUS_PUBLISHED)
            ->whereNull('posts.deleted_at')
            ->where('comments.status', CommunityComment::STATUS_PUBLISHED)
            ->whereNull('comments.deleted_at')
            ->where(function ($query): void {
                $query->whereNull('comments.community_user_id')
                    ->orWhereColumn('comments.community_user_id', '<>', 'posts.community_user_id');
            })
            ->count();
    }

    private function acceptedAnswers(int $userId): int
    {
        return DB::table('community_posts as posts')
            ->join('community_comments as comments', 'comments.id', '=', 'posts.accepted_comment_id')
            ->where('comments.community_user_id', $userId)
            ->where('posts.status', CommunityPost::STATUS_PUBLISHED)
            ->whereNull('posts.deleted_at')
            ->where('comments.status', CommunityComment::STATUS_PUBLISHED)
            ->whereNull('comments.deleted_at')
            ->whereColumn('comments.community_user_id', '<>', 'posts.community_user_id')
            ->count();
    }
}
