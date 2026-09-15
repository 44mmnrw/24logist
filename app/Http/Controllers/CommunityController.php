<?php

namespace App\Http\Controllers;

use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunityPostVote;
use App\Services\Community\CommunitySocialService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        return $this->feed($request);
    }

    public function category(Request $request, CommunityCategory $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->feed($request, $category);
    }

    private function feed(Request $request, ?CommunityCategory $category = null): View
    {
        $sort = in_array($request->query('sort'), ['hot', 'new', 'top', 'unanswered'], true)
            ? (string) $request->query('sort')
            : 'hot';
        $period = in_array($request->query('period'), ['day', 'week', 'month', 'all'], true)
            ? (string) $request->query('period')
            : 'week';
        $search = is_string($request->query('q'))
            ? mb_substr(trim($request->query('q')), 0, 100)
            : '';
        $like = '%'.addcslashes($search, '%_\\').'%';

        $posts = CommunityPost::query()
            ->with(['author', 'category', 'photos'])
            ->published()
            ->when($sort === 'unanswered', fn (Builder $query) => $query
                ->whereNull('resolved_at')
                ->whereDoesntHave('comments', fn (Builder $comments) => $comments->where('status', 'published')))
            ->when($category, fn (Builder $query) => $query->where('community_category_id', $category->id))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($like): void {
                $query->where('title', 'like', $like)->orWhere('body_markdown', 'like', $like);
            }));

        if ($sort === 'new' || $sort === 'unanswered') {
            $posts->orderByDesc('is_pinned')->orderByDesc('published_at')->orderByDesc('id');
        } elseif ($sort === 'top') {
            $since = match ($period) {
                'day' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                default => null,
            };
            $posts->when($since, fn (Builder $query) => $query->where('published_at', '>=', $since))
                ->orderByDesc('is_pinned')->orderByDesc('score')->orderByDesc('published_at');
        } else {
            $posts->orderByDesc('is_pinned')->orderByDesc('hot_score')->orderByDesc('published_at');
        }

        $categories = CommunityCategory::query()
            ->active()
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $posts = $posts->paginate(20)->withQueryString();
        $social = app(CommunitySocialService::class)->summaries('post', $posts->getCollection()->pluck('id')->all(), auth('community')->id());
        $postVotes = auth('community')->check()
            ? CommunityPostVote::query()
                ->where('community_user_id', auth('community')->id())
                ->whereIn('community_post_id', $posts->getCollection()->pluck('id'))
                ->pluck('value', 'community_post_id')
            : collect();

        return view('community.index', [
            'posts' => $posts,
            'postVotes' => $postVotes,
            'categories' => $categories,
            'activeCategory' => $category,
            'sort' => $sort,
            'period' => $period,
            'search' => $search,
            'social' => $social,
        ]);
    }
}
