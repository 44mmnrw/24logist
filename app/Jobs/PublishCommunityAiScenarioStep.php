<?php

namespace App\Jobs;

use App\Models\CommunityAiScenario;
use App\Models\CommunityAiScenarioStep;
use App\Models\CommunityComment;
use App\Models\CommunityCommentVote;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Services\Community\CommunityCommentCounter;
use App\Services\Community\CommunityContentRenderer;
use App\Services\Community\CommunityKarmaService;
use App\Services\Community\CommunityRanking;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PublishCommunityAiScenarioStep implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 120;

    /** @var list<int> */
    public array $backoff = [30, 60, 180, 600];

    public function __construct(public int $stepId)
    {
        $this->onQueue('community-ai');
    }

    public function handle(
        CommunityContentRenderer $renderer,
        CommunityCommentCounter $counter,
        CommunityKarmaService $karma,
    ): void {
        DB::transaction(function () use ($renderer, $counter, $karma): void {
            $step = CommunityAiScenarioStep::query()
                ->with(['scenario', 'persona.communityUser'])
                ->lockForUpdate()
                ->find($this->stepId);

            if ($step === null || $step->published_at !== null || ! in_array($step->status, ['approved', 'scheduled'], true)) {
                return;
            }

            $scenario = $step->scenario;
            if (in_array($scenario->status, [CommunityAiScenario::STATUS_PAUSED, CommunityAiScenario::STATUS_CANCELLED], true)) {
                return;
            }

            if ($step->scheduled_at?->isFuture() === true) {
                self::dispatch($step->id)->delay($step->scheduled_at);

                return;
            }

            $persona = $step->persona;
            $user = $persona->communityUser;
            if (! $persona->is_active || $user === null || $user->trashed() || $user->isRestricted()) {
                throw new RuntimeException('Персона отключена или ограничена модератором.');
            }

            $this->assertDailyLimit($step);
            $publishedAt = $step->scheduled_at ?? now();

            if ($step->type === 'topic') {
                $post = $this->publishTopic($step, $renderer, $publishedAt);
                $step->community_post_id = $post->id;
            } else {
                $comment = $this->publishComment($step, $renderer, $counter, $publishedAt);
                $step->community_post_id = $comment->community_post_id;
                $step->community_comment_id = $comment->id;
                $karma->recalculate($comment->post()->value('community_user_id'));
            }

            $step->status = 'published';
            $step->published_at = $publishedAt;
            $step->last_error = null;
            $step->save();

            $persona->update(['last_acted_at' => now()]);
            $scenario->update([
                'status' => CommunityAiScenario::STATUS_RUNNING,
                'started_at' => $scenario->started_at ?? now(),
                'last_error' => null,
            ]);

            $remaining = $scenario->steps()
                ->whereNotIn('status', ['published', 'skipped', 'cancelled'])
                ->exists();

            if (! $remaining) {
                $scenario->update([
                    'status' => CommunityAiScenario::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }
        });
    }

    private function publishTopic(
        CommunityAiScenarioStep $step,
        CommunityContentRenderer $renderer,
        CarbonInterface $publishedAt,
    ): CommunityPost {
        $scenario = $step->scenario;
        $post = CommunityPost::query()->create([
            'community_user_id' => $step->persona->community_user_id,
            'community_category_id' => $scenario->community_category_id,
            'slug' => Str::slug((string) $step->draft_title) ?: 'topic',
            'title' => trim((string) $step->draft_title),
            'body_markdown' => trim((string) $step->draft_body),
            'body_html' => $renderer->render($step->draft_body),
            'status' => CommunityPost::STATUS_PUBLISHED,
            'published_at' => $publishedAt,
        ]);
        $post->update(['hot_score' => CommunityRanking::hotScore(1, $publishedAt)]);
        CommunityPostVote::query()->firstOrCreate([
            'community_user_id' => $step->persona->community_user_id,
            'community_post_id' => $post->id,
        ], ['value' => 1]);
        $post->forceFill(['created_at' => $publishedAt])->saveQuietly();

        return $post;
    }

    private function publishComment(
        CommunityAiScenarioStep $step,
        CommunityContentRenderer $renderer,
        CommunityCommentCounter $counter,
        CarbonInterface $publishedAt,
    ): CommunityComment {
        $topicStep = $step->scenario->steps()->where('type', 'topic')->first();
        $post = $topicStep?->community_post_id
            ? CommunityPost::query()->published()->lockForUpdate()->find($topicStep->community_post_id)
            : null;

        if ($post === null) {
            throw new RuntimeException('Исходная тема сценария ещё не опубликована.');
        }

        $parent = $step->parent_step_id
            ? CommunityAiScenarioStep::query()->find($step->parent_step_id)?->community_comment_id
            : null;
        $parentComment = $parent ? CommunityComment::query()->find($parent) : null;
        $comment = CommunityComment::query()->create([
            'community_post_id' => $post->id,
            'community_user_id' => $step->persona->community_user_id,
            'parent_id' => $parentComment?->id,
            'root_id' => $parentComment?->root_id,
            'depth' => $parentComment ? $parentComment->depth + 1 : 0,
            'body_markdown' => trim((string) $step->draft_body),
            'body_html' => $renderer->render($step->draft_body),
            'status' => 'published',
        ]);
        if ($parentComment === null) {
            $comment->update(['root_id' => $comment->id]);
        }
        $comment->forceFill(['created_at' => $publishedAt])->saveQuietly();
        CommunityCommentVote::query()->firstOrCreate([
            'community_user_id' => $step->persona->community_user_id,
            'community_comment_id' => $comment->id,
        ], ['value' => 1]);
        $counter->sync($post);

        return $comment;
    }

    private function assertDailyLimit(CommunityAiScenarioStep $step): void
    {
        $persona = $step->persona;
        $userId = $persona->community_user_id;
        $used = $step->type === 'topic'
            ? CommunityPost::query()->where('community_user_id', $userId)->whereDate('created_at', today())->count()
            : CommunityComment::query()->where('community_user_id', $userId)->whereDate('created_at', today())->count();
        $limit = $step->type === 'topic' ? $persona->daily_post_limit : $persona->daily_comment_limit;

        if ($used >= $limit) {
            throw new RuntimeException('Исчерпан дневной лимит персоны.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        $message = $exception ? mb_substr($exception->getMessage(), 0, 2000) : 'Публикация завершилась ошибкой.';
        $step = CommunityAiScenarioStep::query()->find($this->stepId);
        if ($step === null || $step->published_at !== null) {
            return;
        }

        $step->update(['status' => 'failed', 'last_error' => $message]);
        $step->scenario()->update(['status' => CommunityAiScenario::STATUS_FAILED, 'last_error' => $message]);
    }
}
