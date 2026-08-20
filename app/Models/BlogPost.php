<?php

namespace App\Models;

use App\Support\LandingMedia;
use App\Support\RichContent\FontSizeRichContentPlugin;
use App\Support\RichContent\HeadingAnchorRichContentPlugin;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
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
        'author_type',
        'author_url',
        'category',
        'blog_category_id',
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
        'twitter_title',
        'twitter_description',
        'twitter_image_path',
        'twitter_card',
        'schema_type',
        'schema_headline',
        'schema_description',
        'schema_image_path',
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

    /**
     * @return BelongsTo<BlogCategory, BlogPost>
     */
    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
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

    public function displayCategory(): ?string
    {
        $name = trim((string) ($this->blogCategory?->name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $legacyCategory = trim((string) $this->category);

        return $legacyCategory !== '' ? $legacyCategory : null;
    }

    public function renderBody(): string
    {
        return RichContentRenderer::make($this->body ?? '')
            ->plugins([
                FontSizeRichContentPlugin::make(),
                HeadingAnchorRichContentPlugin::make(),
            ])
            ->toHtml();
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
            $post->twitter_image_path = LandingMedia::normalizePath($post->twitter_image_path);
            $post->schema_image_path = LandingMedia::normalizePath($post->schema_image_path);

            if ($post->is_published && $post->published_at === null) {
                $post->published_at = now();
            }
        });

        static::saved(function (self $post): void {
            if (! Schema::hasTable('blog_tags')) {
                return;
            }

            foreach ((array) $post->tags as $value) {
                $name = trim((string) $value);

                if ($name === '') {
                    continue;
                }

                BlogTag::query()->firstOrCreate(
                    ['name' => $name],
                    ['slug' => Str::slug($name) ?: 'tag-'.substr(md5($name), 0, 10)],
                );
            }
        });
    }
}
