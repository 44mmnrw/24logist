<?php

namespace App\Models;

use App\Support\LandingMedia;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'excerpt',
        'body',
        'cover_image_path',
        'cover_image_alt',
        'author_name',
        'category',
        'tags',
        'reading_time_minutes',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_robots',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_path',
        'og_type',
        'is_published',
        'is_featured',
        'published_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function getUrl(): string
    {
        return route('blog.show', $this->slug);
    }

    public function displayTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function displayExcerpt(): ?string
    {
        $excerpt = trim(strip_tags((string) $this->excerpt));

        if ($excerpt !== '') {
            return $excerpt;
        }

        $body = trim(strip_tags($this->renderBody()));

        return $body !== '' ? Str::limit($body, 220) : null;
    }

    public function renderBody(): string
    {
        return RichContentRenderer::make($this->body ?? '')->toHtml();
    }

    public function coverImageUrl(): ?string
    {
        return LandingMedia::url($this->cover_image_path);
    }

    public function publishedDate(): ?Carbon
    {
        return $this->published_at ?: $this->created_at;
    }

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            $post->slug = Str::slug($post->slug);
            $post->cover_image_path = LandingMedia::normalizePath($post->cover_image_path);
            $post->og_image_path = LandingMedia::normalizePath($post->og_image_path);

            if ($post->is_published && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }
}
