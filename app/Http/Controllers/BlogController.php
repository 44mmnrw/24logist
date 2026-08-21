<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogPostRedirect;
use App\Models\BlogTag;
use Illuminate\Database\Eloquent\Collection;
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

        $categories = $this->navigationCategories();

        return view('blog.index', compact('featuredPost', 'posts', 'categories'));
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

    public function category(BlogCategory $blogCategory): View
    {
        abort_unless($blogCategory->is_active, 404);

        $category = $blogCategory;
        $posts = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->whereBelongsTo($category, 'blogCategory')
            ->newestFirst()
            ->paginate(9);

        $categories = $this->navigationCategories();

        return view('blog.category', compact('category', 'posts', 'categories'));
    }

    public function show(string $slug): View|RedirectResponse
    {
        $post = BlogPost::query()
            ->with('blogCategory')
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            $redirect = BlogPostRedirect::query()
                ->where('slug', $slug)
                ->whereHas('blogPost', fn ($query) => $query->published())
                ->with('blogPost')
                ->firstOrFail();

            return redirect()->to($redirect->blogPost->getUrl(), 301);
        }

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

    /** @return Collection<int, BlogCategory> */
    private function navigationCategories(): Collection
    {
        return BlogCategory::query()
            ->where('is_active', true)
            ->whereHas('posts', fn ($query) => $query->published())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
