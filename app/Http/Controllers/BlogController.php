<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $featuredPost = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->latest('published_at')
            ->first();

        $posts = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->when($featuredPost, fn ($query) => $query->whereKeyNot($featuredPost->getKey()))
            ->orderBy('sort_order')
            ->latest('published_at')
            ->paginate(9);

        return view('blog.index', compact('featuredPost', 'posts'));
    }

    public function tag(Request $request): View|RedirectResponse
    {
        $tag = trim((string) $request->query('tag'));

        if ($tag === '') {
            return redirect()->route('blog.index');
        }

        abort_if(mb_strlen($tag) > 100, 404);

        $posts = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->whereJsonContains('tags', $tag)
            ->orderBy('sort_order')
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('blog.tag', compact('tag', 'posts'));
    }

    public function show(string $slug): View
    {
        $post = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedPosts = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->whereKeyNot($post->getKey())
            ->when(
                $post->blog_category_id,
                fn ($query) => $query->where('blog_category_id', $post->blog_category_id),
                fn ($query) => $query->when($post->category, fn ($query) => $query->where('category', $post->category)),
            )
            ->orderBy('sort_order')
            ->latest('published_at')
            ->limit(3)
            ->get();

        if ($relatedPosts->count() < 3) {
            $fallback = BlogPost::query()
                ->with('blogCategory')
                ->published()
                ->whereKeyNot($post->getKey())
                ->whereNotIn('id', $relatedPosts->pluck('id'))
                ->orderBy('sort_order')
                ->latest('published_at')
                ->limit(3 - $relatedPosts->count())
                ->get();

            $relatedPosts = $relatedPosts->concat($fallback);
        }

        return view('blog.show', compact('post', 'relatedPosts'));
    }
}
