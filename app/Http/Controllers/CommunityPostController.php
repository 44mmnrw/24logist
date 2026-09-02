<?php

namespace App\Http\Controllers;

use App\Models\CommunityCategory;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Models\CommunityUser;
use App\Services\Community\CommunityContentRenderer;
use App\Services\Community\CommunityRanking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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

    public function store(Request $request, CommunityContentRenderer $renderer): RedirectResponse
    {
        $user = $this->assertCanPublish();
        $data = $request->validate([
            'community_category_id' => ['required', 'integer', 'exists:community_categories,id'],
            'title' => ['required', 'string', 'max:'.config('community.limits.post_title')],
            'body_markdown' => ['nullable', 'string', 'max:'.config('community.limits.post_body')],
            'external_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        if (blank($data['body_markdown'] ?? null) === blank($data['external_url'] ?? null)) {
            throw ValidationException::withMessages([
                'body_markdown' => 'Выберите один формат: текст публикации или внешняя ссылка.',
            ]);
        }

        $category = CommunityCategory::query()->active()->where('posting_enabled', true)->findOrFail($data['community_category_id']);

        $post = DB::transaction(function () use ($data, $category, $user, $renderer): CommunityPost {
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

            return $post;
        });

        return redirect($post->getUrl())->with('status', 'Тема опубликована.');
    }

    public function show(Request $request, CommunityPost $post, ?string $slug = null): View|RedirectResponse
    {
        abort_unless($post->status === 'published', 404);

        if ($slug !== $post->slug) {
            return redirect($post->getUrl(), 301);
        }

        $post->load(['author', 'category']);
        $roots = CommunityComment::query()
            ->with('author')
            ->where('community_post_id', $post->id)
            ->whereNull('parent_id')
            ->whereIn('status', ['published', 'deleted'])
            ->orderByDesc('score')
            ->orderBy('created_at')
            ->paginate(20);
        $rootIds = $roots->getCollection()->pluck('id');
        $descendants = $rootIds->isEmpty()
            ? collect()
            : CommunityComment::query()
                ->with('author')
                ->where('community_post_id', $post->id)
                ->whereIn('root_id', $rootIds)
                ->whereNotIn('id', $rootIds)
                ->whereIn('status', ['published', 'deleted'])
                ->orderByDesc('score')
                ->orderBy('created_at')
                ->get();
        $children = $descendants->groupBy(fn (CommunityComment $comment): int => (int) $comment->parent_id);

        $postVote = null;
        $commentVotes = collect();

        if ($userId = auth('community')->id()) {
            $postVote = CommunityPostVote::query()->where('community_user_id', $userId)->where('community_post_id', $post->id)->value('value');
            $commentVotes = DB::table('community_comment_votes')
                ->where('community_user_id', $userId)
                ->whereIn('community_comment_id', $rootIds->merge($descendants->pluck('id')))
                ->pluck('value', 'community_comment_id');
        }

        return view('community.posts.show', compact('post', 'roots', 'children', 'postVote', 'commentVotes'));
    }

    public function edit(CommunityPost $post): View
    {
        $this->assertOwner($post);

        return view('community.posts.form', [
            'post' => $post,
            'categories' => CommunityCategory::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, CommunityPost $post, CommunityContentRenderer $renderer): RedirectResponse
    {
        $this->assertOwner($post);
        abort_if($post->locked_at !== null, 423, 'Обсуждение заблокировано.');

        $data = $request->validate([
            'community_category_id' => ['required', 'integer', 'exists:community_categories,id'],
            'title' => ['required', 'string', 'max:'.config('community.limits.post_title')],
            'body_markdown' => ['nullable', 'string', 'max:'.config('community.limits.post_body')],
            'external_url' => ['nullable', 'url:http,https', 'max:2048'],
        ]);

        if (blank($data['body_markdown'] ?? null) === blank($data['external_url'] ?? null)) {
            throw ValidationException::withMessages(['body_markdown' => 'Выберите один формат: текст или ссылка.']);
        }

        CommunityCategory::query()->active()->where('posting_enabled', true)->findOrFail($data['community_category_id']);

        $post->update([
            'community_category_id' => $data['community_category_id'],
            'title' => trim($data['title']),
            'slug' => Str::slug($data['title']) ?: $post->slug,
            'body_markdown' => filled($data['body_markdown'] ?? null) ? trim($data['body_markdown']) : null,
            'body_html' => $renderer->render($data['body_markdown'] ?? null),
            'external_url' => filled($data['external_url'] ?? null) ? trim($data['external_url']) : null,
            'edited_at' => now(),
        ]);

        return redirect($post->getUrl())->with('status', 'Тема обновлена.');
    }

    public function destroy(CommunityPost $post): RedirectResponse
    {
        $this->assertOwner($post);
        $post->update([
            'community_user_id' => null,
            'title' => '[тема удалена]',
            'body_markdown' => null,
            'body_html' => null,
            'external_url' => null,
            'status' => 'deleted',
        ]);

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
