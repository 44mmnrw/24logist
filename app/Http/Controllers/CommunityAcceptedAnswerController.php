<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Services\Community\CommunityKarmaService;
use App\Services\Community\CommunityNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CommunityAcceptedAnswerController extends Controller
{
    public function store(
        CommunityPost $post,
        CommunityComment $comment,
        CommunityNotificationService $notifications,
        CommunityKarmaService $karma,
    ): RedirectResponse {
        $this->authorizeChange($post);
        $affectedUserIds = DB::transaction(function () use ($post, $comment): array {
            $lockedPost = CommunityPost::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            $previousAuthorId = $lockedPost->accepted_comment_id === null
                ? null
                : CommunityComment::query()->whereKey($lockedPost->accepted_comment_id)->value('community_user_id');
            $lockedComment = CommunityComment::query()->whereKey($comment->id)
                ->where('community_post_id', $post->id)
                ->where('status', 'published')
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPost->update(['accepted_comment_id' => $lockedComment->id, 'resolved_at' => now()]);

            return [$previousAuthorId, $lockedComment->community_user_id];
        });
        $karma->recalculateMany($affectedUserIds);

        if ($comment->author !== null) {
            $notifications->create(
                $comment->author,
                auth('community')->user(),
                'answer_accepted',
                'comment',
                $comment->id,
                [
                    'message' => 'Ваш ответ в теме «'.$post->title.'» отмечен как решение.',
                    'url' => $post->getUrl().'#comment-'.$comment->id,
                ],
            );
        }

        return redirect($post->getUrl().'#comment-'.$comment->id)->with('status', 'Ответ отмечен как решение.');
    }

    public function destroy(CommunityPost $post, CommunityKarmaService $karma): RedirectResponse
    {
        $this->authorizeChange($post);
        $answerAuthorId = $post->accepted_comment_id === null
            ? null
            : CommunityComment::query()->whereKey($post->accepted_comment_id)->value('community_user_id');
        $post->update(['accepted_comment_id' => null, 'resolved_at' => null]);
        $karma->recalculate($answerAuthorId === null ? null : (int) $answerAuthorId);

        return redirect($post->getUrl())->with('status', 'Отметка решения снята.');
    }

    private function authorizeChange(CommunityPost $post): void
    {
        $user = auth('community')->user();
        abort_unless($post->status === 'published', 404);
        abort_unless($user && ($post->community_user_id === $user->id || $user->isModerator()), 403);
    }
}
