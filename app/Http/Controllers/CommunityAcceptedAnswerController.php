<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Services\Community\CommunityNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CommunityAcceptedAnswerController extends Controller
{
    public function store(CommunityPost $post, CommunityComment $comment, CommunityNotificationService $notifications): RedirectResponse
    {
        $this->authorizeChange($post);
        DB::transaction(function () use ($post, $comment): void {
            $lockedPost = CommunityPost::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            $lockedComment = CommunityComment::query()->whereKey($comment->id)
                ->where('community_post_id', $post->id)
                ->where('status', 'published')
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPost->update(['accepted_comment_id' => $lockedComment->id, 'resolved_at' => now()]);
        });

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

    public function destroy(CommunityPost $post): RedirectResponse
    {
        $this->authorizeChange($post);
        $post->update(['accepted_comment_id' => null, 'resolved_at' => null]);

        return redirect($post->getUrl())->with('status', 'Отметка решения снята.');
    }

    private function authorizeChange(CommunityPost $post): void
    {
        $user = auth('community')->user();
        abort_unless($post->status === 'published', 404);
        abort_unless($user && ($post->community_user_id === $user->id || $user->isModerator()), 403);
    }
}
