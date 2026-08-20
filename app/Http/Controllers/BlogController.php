<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\BlogTag;
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
            ->newestFirst()
            ->first();

        $posts = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->when($featuredPost, fn ($query) => $query->whereKeyNot($featuredPost->getKey()))
            ->newestFirst()
            ->paginate(9);

        return view('blog.index', compact('featuredPost', 'posts'));
    }

    public function legacyTag(Request $request): RedirectResponse
    {
        $name = trim((string) $request->query('tag'));

        if ($name === '') {
            return redirect()->route('blog.index');
        }

        $tag = BlogTag::query()->where('name', $name)->firstOrFail();

        return redirect()->route('blog.tag', $tag->slug, 301);
    }

    public function tag(BlogTag $blogTag): View
    {
        $tag = $blogTag;

        $posts = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->whereJsonContains('tags', $tag->name)
            ->newestFirst()
            ->paginate(9);

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
            ->newestFirst()
            ->limit(3)
            ->get();

        if ($relatedPosts->count() < 3) {
            $fallback = BlogPost::query()
                ->with('blogCategory')
                ->published()
                ->whereKeyNot($post->getKey())
                ->whereNotIn('id', $relatedPosts->pluck('id'))
                ->newestFirst()
                ->limit(3 - $relatedPosts->count())
                ->get();

            $relatedPosts = $relatedPosts->concat($fallback);
        }

        $tagLinks = BlogTag::query()
            ->whereIn('name', array_values(array_filter((array) $post->tags, fn ($tag): bool => filled($tag))))
            ->get()
            ->keyBy('name');

        return view('blog.show', compact('post', 'relatedPosts', 'tagLinks'));
    }
}
