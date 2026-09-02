<?php

namespace App\Services\Community;

use App\Models\CommunityComment;
use App\Models\CommunityCommentVote;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Models\CommunityUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CommunityVotingService
{
    /** @return array{score: int, user_vote: int} */
    public function vote(CommunityUser $user, string $type, int $targetId, int $value): array
    {
        if (! in_array($value, [-1, 0, 1], true)) {
            throw new InvalidArgumentException('Invalid vote value.');
        }

        return DB::transaction(function () use ($user, $type, $targetId, $value): array {
            [$target, $voteClass, $targetColumn] = match ($type) {
                'post' => [CommunityPost::query()->published()->lockForUpdate()->findOrFail($targetId), CommunityPostVote::class, 'community_post_id'],
                'comment' => [CommunityComment::query()->where('status', 'published')->lockForUpdate()->findOrFail($targetId), CommunityCommentVote::class, 'community_comment_id'],
                default => throw new InvalidArgumentException('Invalid vote target.'),
            };

            $vote = $voteClass::query()
                ->where('community_user_id', $user->id)
                ->where($targetColumn, $targetId)
                ->lockForUpdate()
                ->first();
            $previous = (int) ($vote?->value ?? 0);

            if ($previous === $value) {
                return ['score' => (int) $target->score, 'user_vote' => $value];
            }

            if ($value === 0) {
                $vote?->delete();
            } elseif ($vote !== null) {
                $vote->update(['value' => $value]);
            } else {
                $voteClass::query()->create([
                    'community_user_id' => $user->id,
                    $targetColumn => $targetId,
                    'value' => $value,
                ]);
            }

            $delta = $value - $previous;
            $target->score += $delta;

            if ($target instanceof CommunityPost) {
                $target->hot_score = CommunityRanking::hotScore((int) $target->score, $target->published_at ?? $target->created_at);
            }

            $target->save();
            $this->updateAuthorKarma($target, $user, $delta);

            return ['score' => (int) $target->score, 'user_vote' => $value];
        });
    }

    private function updateAuthorKarma(Model $target, CommunityUser $voter, int $delta): void
    {
        $authorId = $target->community_user_id;

        if ($authorId === null || (int) $authorId === (int) $voter->id || $delta === 0) {
            return;
        }

        CommunityUser::query()->whereKey($authorId)->increment('karma', $delta);
    }
}
