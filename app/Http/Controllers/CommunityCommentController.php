<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityCommentVote;
use App\Models\CommunityPost;
use App\Models\CommunityPostSubscription;
use App\Models\CommunityUser;
use App\Services\Community\CommunityCommentCounter;
use App\Services\Community\CommunityContentRenderer;
use App\Services\Community\CommunityKarmaService;
use App\Services\Community\CommunityNotificationService;
use App\Services\Community\CommunityPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CommunityCommentController extends Controller
{
    public function store(
        Request $request,
        CommunityPost $post,
        CommunityContentRenderer $renderer,
        CommunityCommentCounter $counter,
        CommunityNotificationService $notifications,
        CommunityPhotoService $photos,
        CommunityKarmaService $karma,
    ): RedirectResponse {
        $user = auth('community')->user();
        abort_if($user->isRestricted(), 403, 'Комментарии для аккаунта временно ограничены.');
        abort_if($post->status !== 'published' || $post->locked_at !== null, 423, 'Обсуждение закрыто.');
        $data = $request->validate([
            'body_markdown' => ['nullable', 'string', 'max:'.config('community.limits.comment_body')],
            'parent_id' => ['nullable', 'integer'],
            'photos' => ['sometimes', 'array', 'max:'.CommunityPhotoService::MAX_COUNT],
            'photos.*' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.CommunityPhotoService::MAX_FILE_KB, 'dimensions:max_width='.CommunityPhotoService::MAX_SOURCE_EDGE.',max_height='.CommunityPhotoService::MAX_SOURCE_EDGE],
        ]);

        if (blank($data['body_markdown'] ?? null) && empty($data['photos'])) {
            throw ValidationException::withMessages(['body_markdown' => 'Добавьте текст или фото.']);
        }

        $parent = null;
        $depth = 0;

        if (filled($data['parent_id'] ?? null)) {
            $parent = CommunityComment::query()->where('community_post_id', $post->id)->where('status', 'published')->findOrFail($data['parent_id']);
            $depth = $parent->depth + 1;

            if ($depth >= (int) config('community.limits.comment_depth', 6)) {
                throw ValidationException::withMessages(['parent_id' => 'Достигнута максимальная глубина обсуждения.']);
            }
        }

        $storedPhotos = $photos->storeUploads($data['photos'] ?? []);

        try {
            $comment = DB::transaction(function () use ($post, $user, $parent, $depth, $data, $renderer, $counter, $storedPhotos): CommunityComment {
                $lockedPost = CommunityPost::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
                $comment = CommunityComment::query()->create([
                    'community_post_id' => $post->id,
                    'community_user_id' => $user->id,
                    'parent_id' => $parent?->id,
                    'root_id' => $parent?->root_id,
                    'depth' => $depth,
                    'body_markdown' => filled($data['body_markdown'] ?? null) ? trim($data['body_markdown']) : null,
                    'body_html' => $renderer->render($data['body_markdown'] ?? null),
                ]);

                if ($parent === null) {
                    $comment->update(['root_id' => $comment->id]);
                }

                CommunityCommentVote::query()->create([
                    'community_user_id' => $user->id,
                    'community_comment_id' => $comment->id,
                    'value' => 1,
                ]);
                foreach ($storedPhotos as $position => $photo) {
                    $comment->photos()->create($photo + ['position' => $position]);
                }
                $counter->sync($lockedPost);

                return $comment;
            });
        } catch (Throwable $e) {
            $photos->deleteFiles($storedPhotos);

            throw $e;
        }

        $karma->recalculate($post->community_user_id);

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

        CommunityPostSubscription::query()
            ->where('community_post_id', $post->id)
            ->whereNotIn('community_user_id', array_filter([$user->id, $recipient?->id]))
            ->with('subscriber')
            ->each(function (CommunityPostSubscription $subscription) use ($notifications, $user, $post, $comment): void {
                $notifications->create(
                    $subscription->subscriber,
                    $user,
                    'post_reply',
                    'comment',
                    $comment->id,
                    [
                        'message' => $user->displayName().' ответил в теме «'.$post->title.'»',
                        'url' => $post->getUrl().'#comment-'.$comment->id,
                    ],
                );
            });

        return redirect($post->getUrl().'#comment-'.$comment->id)->with('status', 'Комментарий опубликован.');
    }

    public function update(Request $request, CommunityComment $comment, CommunityContentRenderer $renderer, CommunityPhotoService $photos): RedirectResponse
    {
        $this->assertOwner($comment);
        abort_if($comment->post->locked_at !== null || $comment->status !== 'published', 423);
        $data = $request->validate([
            'body_markdown' => ['nullable', 'string', 'max:'.config('community.limits.comment_body')],
            'photos' => ['sometimes', 'array', 'max:'.CommunityPhotoService::MAX_COUNT],
            'photos.*' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.CommunityPhotoService::MAX_FILE_KB, 'dimensions:max_width='.CommunityPhotoService::MAX_SOURCE_EDGE.',max_height='.CommunityPhotoService::MAX_SOURCE_EDGE],
            'remove_photos' => ['sometimes', 'array'],
            'remove_photos.*' => ['integer', 'distinct'],
        ]);

        $existingPhotos = $comment->photos()->get();
        $removeIds = array_map('intval', $data['remove_photos'] ?? []);
        $removedPhotos = $existingPhotos->whereIn('id', $removeIds);
        if ($removedPhotos->count() !== count($removeIds)) {
            throw ValidationException::withMessages(['remove_photos' => 'Выбрано чужое или уже удалённое фото.']);
        }

        $remainingCount = $existingPhotos->count() - $removedPhotos->count();
        if ($remainingCount + count($data['photos'] ?? []) > CommunityPhotoService::MAX_COUNT) {
            throw ValidationException::withMessages(['photos' => 'В ответе может быть не больше 3 фото.']);
        }
        if (blank($data['body_markdown'] ?? null) && $remainingCount + count($data['photos'] ?? []) === 0) {
            throw ValidationException::withMessages(['body_markdown' => 'Добавьте текст или фото.']);
        }

        $storedPhotos = $photos->storeUploads($data['photos'] ?? []);
        try {
            DB::transaction(function () use ($comment, $data, $renderer, $removeIds, $storedPhotos): void {
                $comment->update([
                    'body_markdown' => filled($data['body_markdown'] ?? null) ? trim($data['body_markdown']) : null,
                    'body_html' => $renderer->render($data['body_markdown'] ?? null),
                    'edited_at' => now(),
                ]);
                $comment->photos()->whereIn('id', $removeIds)->delete();
                $remaining = $comment->photos()->get();
                foreach ($remaining as $position => $photo) {
                    $photo->update(['position' => $position]);
                }
                foreach ($storedPhotos as $index => $photo) {
                    $comment->photos()->create($photo + ['position' => $remaining->count() + $index]);
                }
            });
        } catch (Throwable $e) {
            $photos->deleteFiles($storedPhotos);

            throw $e;
        }
        $photos->deleteFiles($removedPhotos);

        return redirect($comment->post->getUrl().'#comment-'.$comment->id)->with('status', 'Комментарий обновлён.');
    }

    public function destroy(
        CommunityComment $comment,
        CommunityCommentCounter $counter,
        CommunityPhotoService $photos,
        CommunityKarmaService $karma,
    ): RedirectResponse {
        $this->assertOwner($comment);
        $post = $comment->post;
        $karmaUserIds = [$post->community_user_id, $comment->community_user_id];
        $attachedPhotos = $comment->photos()->get();
        DB::transaction(function () use ($post, $comment, $counter): void {
            $lockedPost = CommunityPost::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            $lockedComment = CommunityComment::query()->whereKey($comment->id)->lockForUpdate()->firstOrFail();
            abort_if($lockedComment->status === 'deleted', 404);
            $lockedComment->update([
                'community_user_id' => null,
                'body_markdown' => null,
                'body_html' => null,
                'status' => 'deleted',
            ]);
            if ($lockedPost->accepted_comment_id === $lockedComment->id) {
                $lockedPost->update(['accepted_comment_id' => null, 'resolved_at' => null]);
            }
            $lockedComment->photos()->delete();
            $counter->sync($lockedPost);
        });

        $karma->recalculateMany($karmaUserIds);

        $photos->deleteFiles($attachedPhotos);

        return redirect($post->getUrl().'#comment-'.$comment->id)->with('status', 'Комментарий удалён.');
    }

    private function assertOwner(CommunityComment $comment): void
    {
        $user = auth('community')->user();
        abort_unless($user !== null && ($comment->community_user_id === $user->id || $user->isModerator()), 403);
    }
}
