<?php

namespace App\Http\Controllers;

use App\Models\CommunityCategory;
use App\Models\CommunityComment;
use App\Models\CommunityPhoto;
use App\Models\CommunityPost;
use App\Models\CommunityPostSubscription;
use App\Models\CommunityPostVote;
use App\Models\CommunityUser;
use App\Services\Community\CommunityContentRenderer;
use App\Services\Community\CommunityPhotoService;
use App\Services\Community\CommunityRanking;
use App\Services\Community\CommunitySocialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CommunityPostController extends Controller
{
    public function create(): View
    {
        $this->assertCanPublish();

        return view('community.posts.form', [
            'post' => new CommunityPost,
            'categories' => CommunityCategory::query()->active()->where('posting_enabled', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request, CommunityContentRenderer $renderer, CommunityPhotoService $photos): RedirectResponse
    {
        $user = $this->assertCanPublish();
        $data = $request->validate([
            'community_category_id' => ['required', 'integer', 'exists:community_categories,id'],
            'title' => ['required', 'string', 'max:'.config('community.limits.post_title')],
            'body_markdown' => ['nullable', 'string', 'max:'.config('community.limits.post_body')],
            'external_url' => ['nullable', 'url:http,https', 'max:2048'],
            'photos' => ['sometimes', 'array', 'max:'.CommunityPhotoService::MAX_COUNT],
            'photos.*' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.CommunityPhotoService::MAX_FILE_KB, 'dimensions:max_width='.CommunityPhotoService::MAX_SOURCE_EDGE.',max_height='.CommunityPhotoService::MAX_SOURCE_EDGE],
        ]);

        if (filled($data['body_markdown'] ?? null) && filled($data['external_url'] ?? null)) {
            throw ValidationException::withMessages([
                'body_markdown' => 'Выберите текст публикации или внешнюю ссылку.',
            ]);
        }

        if (blank($data['body_markdown'] ?? null) && blank($data['external_url'] ?? null) && empty($data['photos'])) {
            throw ValidationException::withMessages(['body_markdown' => 'Добавьте текст, ссылку или фото.']);
        }

        $category = CommunityCategory::query()->active()->where('posting_enabled', true)->findOrFail($data['community_category_id']);

        $storedPhotos = $photos->storeUploads($data['photos'] ?? []);

        try {
            $post = DB::transaction(function () use ($data, $category, $user, $renderer, $storedPhotos): CommunityPost {
                $post = CommunityPost::query()->create([
                    'community_user_id' => $user->id,
                    'community_category_id' => $category->id,
                    'slug' => Str::slug($data['title']) ?: 'topic',
                    'title' => trim($data['title']),
                    'body_markdown' => filled($data['body_markdown'] ?? null) ? trim($data['body_markdown']) : null,
                    'body_html' => $renderer->render($data['body_markdown'] ?? null),
                    'external_url' => filled($data['external_url'] ?? null) ? trim($data['external_url']) : null,
                    'published_at' => now(),
                ]);
                $post->update(['hot_score' => CommunityRanking::hotScore(1, $post->published_at)]);
                CommunityPostVote::query()->create([
                    'community_user_id' => $user->id,
                    'community_post_id' => $post->id,
                    'value' => 1,
                ]);

                foreach ($storedPhotos as $position => $photo) {
                    $post->photos()->create($photo + ['position' => $position]);
                }

                return $post;
            });
        } catch (Throwable $e) {
            $photos->deleteFiles($storedPhotos);

            throw $e;
        }

        return redirect($post->getUrl())->with('status', 'Тема опубликована.');
    }

    public function show(Request $request, CommunityPost $post, ?string $slug = null): View|RedirectResponse
    {
        abort_unless($post->status === 'published', 404);

        if ($slug !== $post->slug) {
            return redirect($post->getUrl(), 301);
        }

        $post->load(['author', 'category', 'photos']);
        $communityStats = [
            'members' => CommunityUser::query()->count(),
            'topics' => CommunityPost::query()->published()->count(),
        ];
        $commentSort = in_array($request->query('comment_sort'), ['best', 'new', 'old'], true)
            ? (string) $request->query('comment_sort')
            : 'best';
        $rootsQuery = CommunityComment::query()
            ->with(['author', 'photos'])
            ->where('community_post_id', $post->id)
            ->whereNull('parent_id')
            ->whereIn('status', ['published', 'deleted', 'hidden']);

        match ($commentSort) {
            'new' => $rootsQuery->orderByDesc('created_at'),
            'old' => $rootsQuery->orderBy('created_at'),
            default => $rootsQuery->orderByDesc('score')->orderBy('created_at'),
        };

        $roots = $rootsQuery->paginate(20)->withQueryString();
        $rootIds = $roots->getCollection()->pluck('id');
        $descendants = $rootIds->isEmpty()
            ? collect()
            : CommunityComment::query()
                ->with(['author', 'photos'])
                ->where('community_post_id', $post->id)
                ->whereIn('root_id', $rootIds)
                ->whereNotIn('id', $rootIds)
                ->whereIn('status', ['published', 'deleted', 'hidden'])
                ->orderByDesc('score')
                ->orderBy('created_at')
                ->get();
        $children = $descendants->groupBy(fn (CommunityComment $comment): int => (int) $comment->parent_id);
        $socialService = app(CommunitySocialService::class);
        $postSocial = $socialService->summary('post', $post->id, auth('community')->id());
        $commentSocial = $socialService->summaries('comment', $rootIds->merge($descendants->pluck('id'))->all(), auth('community')->id());

        $postVote = null;
        $subscribed = false;
        $commentVotes = collect();

        if ($userId = auth('community')->id()) {
            $postVote = CommunityPostVote::query()->where('community_user_id', $userId)->where('community_post_id', $post->id)->value('value');
            $subscribed = CommunityPostSubscription::query()->where('community_user_id', $userId)->where('community_post_id', $post->id)->exists();
            $commentVotes = DB::table('community_comment_votes')
                ->where('community_user_id', $userId)
                ->whereIn('community_comment_id', $rootIds->merge($descendants->pluck('id')))
                ->pluck('value', 'community_comment_id');
        }

        return view('community.posts.show', compact('post', 'roots', 'children', 'postVote', 'subscribed', 'commentVotes', 'commentSort', 'communityStats', 'postSocial', 'commentSocial'));
    }

    public function edit(CommunityPost $post): View
    {
        $this->assertOwner($post);
        $post->load('photos');

        return view('community.posts.form', [
            'post' => $post,
            'categories' => CommunityCategory::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, CommunityPost $post, CommunityContentRenderer $renderer, CommunityPhotoService $photos): RedirectResponse
    {
        $this->assertOwner($post);
        abort_if($post->locked_at !== null, 423, 'Обсуждение заблокировано.');

        $data = $request->validate([
            'community_category_id' => ['required', 'integer', 'exists:community_categories,id'],
            'title' => ['required', 'string', 'max:'.config('community.limits.post_title')],
            'body_markdown' => ['nullable', 'string', 'max:'.config('community.limits.post_body')],
            'external_url' => ['nullable', 'url:http,https', 'max:2048'],
            'photos' => ['sometimes', 'array', 'max:'.CommunityPhotoService::MAX_COUNT],
            'photos.*' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:'.CommunityPhotoService::MAX_FILE_KB, 'dimensions:max_width='.CommunityPhotoService::MAX_SOURCE_EDGE.',max_height='.CommunityPhotoService::MAX_SOURCE_EDGE],
            'remove_photos' => ['sometimes', 'array'],
            'remove_photos.*' => ['integer', 'distinct'],
        ]);

        if (filled($data['body_markdown'] ?? null) && filled($data['external_url'] ?? null)) {
            throw ValidationException::withMessages(['body_markdown' => 'Выберите текст публикации или внешнюю ссылку.']);
        }

        CommunityCategory::query()->active()->where('posting_enabled', true)->findOrFail($data['community_category_id']);

        $existingPhotos = $post->photos()->get();
        $removeIds = array_map('intval', $data['remove_photos'] ?? []);
        $removedPhotos = $existingPhotos->whereIn('id', $removeIds);
        if ($removedPhotos->count() !== count($removeIds)) {
            throw ValidationException::withMessages(['remove_photos' => 'Выбрано чужое или уже удалённое фото.']);
        }

        $remainingCount = $existingPhotos->count() - $removedPhotos->count();
        if ($remainingCount + count($data['photos'] ?? []) > CommunityPhotoService::MAX_COUNT) {
            throw ValidationException::withMessages(['photos' => 'В публикации может быть не больше 3 фото.']);
        }

        if (blank($data['body_markdown'] ?? null) && blank($data['external_url'] ?? null)
            && $remainingCount + count($data['photos'] ?? []) === 0) {
            throw ValidationException::withMessages(['body_markdown' => 'Добавьте текст, ссылку или фото.']);
        }

        $storedPhotos = $photos->storeUploads($data['photos'] ?? []);

        try {
            DB::transaction(function () use ($post, $data, $renderer, $removeIds, $storedPhotos): void {
                $post->update([
                    'community_category_id' => $data['community_category_id'],
                    'title' => trim($data['title']),
                    'slug' => Str::slug($data['title']) ?: $post->slug,
                    'body_markdown' => filled($data['body_markdown'] ?? null) ? trim($data['body_markdown']) : null,
                    'body_html' => $renderer->render($data['body_markdown'] ?? null),
                    'external_url' => filled($data['external_url'] ?? null) ? trim($data['external_url']) : null,
                    'edited_at' => now(),
                ]);
                $post->photos()->whereIn('id', $removeIds)->delete();
                $remaining = $post->photos()->get();
                foreach ($remaining as $position => $photo) {
                    $photo->update(['position' => $position]);
                }
                foreach ($storedPhotos as $index => $photo) {
                    $post->photos()->create($photo + ['position' => $remaining->count() + $index]);
                }
            });
        } catch (Throwable $e) {
            $photos->deleteFiles($storedPhotos);

            throw $e;
        }

        $photos->deleteFiles($removedPhotos);

        return redirect($post->getUrl())->with('status', 'Тема обновлена.');
    }

    public function destroy(CommunityPost $post, CommunityPhotoService $photos): RedirectResponse
    {
        $this->assertOwner($post);
        $attachedPhotos = CommunityPhoto::query()
            ->where('community_post_id', $post->id)
            ->orWhereIn('community_comment_id', DB::table('community_comments')->select('id')->where('community_post_id', $post->id))
            ->get();
        DB::transaction(function () use ($post, $attachedPhotos): void {
            $post->update([
                'community_user_id' => null,
                'title' => '[тема удалена]',
                'body_markdown' => null,
                'body_html' => null,
                'external_url' => null,
                'status' => 'deleted',
            ]);
            CommunityPhoto::query()->whereIn('id', $attachedPhotos->pluck('id'))->delete();
            $post->delete();
        });
        $photos->deleteFiles($attachedPhotos);

        return redirect()->route('community.index')->with('status', 'Тема удалена.');
    }

    private function assertCanPublish(): CommunityUser
    {
        $user = auth('community')->user();
        abort_unless($user !== null, 401);
        abort_if($user->isRestricted(), 403, 'Публикация для аккаунта временно ограничена.');

        return $user;
    }

    private function assertOwner(CommunityPost $post): void
    {
        $user = auth('community')->user();
        abort_unless($user !== null && ($post->community_user_id === $user->id || $user->isModerator()), 403);
    }
}
