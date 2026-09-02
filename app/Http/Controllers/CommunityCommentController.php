<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityCommentVote;
use App\Models\CommunityPost;
use App\Models\CommunityUser;
use App\Services\Community\CommunityContentRenderer;
use App\Services\Community\CommunityNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommunityCommentController extends Controller
{
    public function store(
        Request $request,
        CommunityPost $post,
        CommunityContentRenderer $renderer,
        CommunityNotificationService $notifications,
    ): RedirectResponse {
        $user = auth('community')->user();
        abort_if($user->isRestricted(), 403, 'Комментарии для аккаунта временно ограничены.');
        abort_if($post->status !== 'published' || $post->locked_at !== null, 423, 'Обсуждение закрыто.');
        $data = $request->validate([
            'body_markdown' => ['required', 'string', 'max:'.config('community.limits.comment_body')],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parent = null;
        $depth = 0;

        if (filled($data['parent_id'] ?? null)) {
            $parent = CommunityComment::query()->where('community_post_id', $post->id)->findOrFail($data['parent_id']);
            $depth = $parent->depth + 1;

            if ($depth >= (int) config('community.limits.comment_depth', 6)) {
                throw ValidationException::withMessages(['parent_id' => 'Достигнута максимальная глубина обсуждения.']);
            }
        }

        $comment = DB::transaction(function () use ($post, $user, $parent, $depth, $data, $renderer): CommunityComment {
            $comment = CommunityComment::query()->create([
                'community_post_id' => $post->id,
                'community_user_id' => $user->id,
                'parent_id' => $parent?->id,
                'root_id' => $parent?->root_id,
                'depth' => $depth,
                'body_markdown' => trim($data['body_markdown']),
                'body_html' => $renderer->render($data['body_markdown']),
            ]);

            if ($parent === null) {
                $comment->update(['root_id' => $comment->id]);
            }

            CommunityCommentVote::query()->create([
                'community_user_id' => $user->id,
                'community_comment_id' => $comment->id,
                'value' => 1,
            ]);
            CommunityPost::query()->whereKey($post->id)->increment('comments_count');

            return $comment;
        });

        $recipient = $parent?->author ?: $post->author;

        if ($recipient instanceof CommunityUser) {
            $notifications->create(
                $recipient,
                $user,
                $parent ? 'comment_reply' : 'post_reply',
                'comment',
                $comment->id,
                [
                    'message' => $user->displayName().' ответил в теме «'.$post->title.'»',
                    'url' => $post->getUrl().'#comment-'.$comment->id,
                ],
            );
        }

        return redirect($post->getUrl().'#comment-'.$comment->id)->with('status', 'Комментарий опубликован.');
    }

    public function update(Request $request, CommunityComment $comment, CommunityContentRenderer $renderer): RedirectResponse
    {
        $this->assertOwner($comment);
        abort_if($comment->post->locked_at !== null || $comment->status !== 'published', 423);
        $data = $request->validate([
            'body_markdown' => ['required', 'string', 'max:'.config('community.limits.comment_body')],
        ]);
        $comment->update([
            'body_markdown' => trim($data['body_markdown']),
            'body_html' => $renderer->render($data['body_markdown']),
            'edited_at' => now(),
        ]);

        return redirect($comment->post->getUrl().'#comment-'.$comment->id)->with('status', 'Комментарий обновлён.');
    }

    public function destroy(CommunityComment $comment): RedirectResponse
    {
        $this->assertOwner($comment);
        $post = $comment->post;
        $comment->update([
            'community_user_id' => null,
            'body_markdown' => null,
            'body_html' => null,
            'status' => 'deleted',
        ]);
        CommunityPost::query()->whereKey($post->id)->where('comments_count', '>', 0)->decrement('comments_count');

        return redirect($post->getUrl().'#comment-'.$comment->id)->with('status', 'Комментарий удалён.');
    }

    private function assertOwner(CommunityComment $comment): void
    {
        $user = auth('community')->user();
        abort_unless($user !== null && ($comment->community_user_id === $user->id || $user->isModerator()), 403);
    }
}
