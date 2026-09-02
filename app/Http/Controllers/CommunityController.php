<?php

namespace App\Http\Controllers;

use App\Models\CommunityCategory;
use App\Models\CommunityPost;
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
        $sort = in_array($request->query('sort'), ['hot', 'new', 'top'], true)
            ? (string) $request->query('sort')
            : 'hot';
        $period = in_array($request->query('period'), ['day', 'week', 'month', 'all'], true)
            ? (string) $request->query('period')
            : 'week';

        $posts = CommunityPost::query()
            ->with(['author', 'category'])
            ->published()
            ->when($category, fn (Builder $query) => $query->where('community_category_id', $category->id));

        if ($sort === 'new') {
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

        return view('community.index', [
            'posts' => $posts->paginate(20)->withQueryString(),
            'categories' => $categories,
            'activeCategory' => $category,
            'sort' => $sort,
            'period' => $period,
        ]);
    }
}
