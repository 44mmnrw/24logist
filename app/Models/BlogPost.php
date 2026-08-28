<?php

namespace App\Models;

use App\Support\LandingMedia;
use App\Support\BlogBodyImages;
use App\Support\RichContent\FontSizeRichContentPlugin;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BlogPost extends Model
{
    public const LOGO_POSITIONS = [
        'top-left' => 'Сверху слева',
        'top-right' => 'Сверху справа',
        'bottom-left' => 'Снизу слева',
        'bottom-right' => 'Снизу справа',
    ];

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'excerpt',
        'body',
        'cover_image_path',
        'card_source_image_path',
        'card_image_path',
        'show_card_logo',
        'card_logo_position',
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
            'show_card_logo' => 'boolean',
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

    /**
     * @return HasMany<BlogPostRedirect, BlogPost>
     */
    public function redirects(): HasMany
    {
        return $this->hasMany(BlogPostRedirect::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function getUrl(): string
    {
        return route('blog.show', $this->slug);
    }

    public function previewUrl(): string
    {
        return URL::temporarySignedRoute(
            'blog.preview',
            now()->addDays(7),
            ['blogPost' => $this],
        );
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

    public function previewExcerpt(int $limit = 220): ?string
    {
        $excerpt = $this->displayExcerpt();

        if ($excerpt === null) {
            return null;
        }

        $limit = max(1, $limit);

        if (mb_strlen($excerpt) <= $limit) {
            return $excerpt;
        }

        return $limit === 1
            ? '…'
            : rtrim(mb_substr($excerpt, 0, $limit - 1)).'…';
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

    public function categoryUrl(): ?string
    {
        return $this->blogCategory?->is_active
            ? $this->blogCategory->getUrl()
            : null;
    }

    public function renderBody(): string
    {
        $html = RichContentRenderer::make($this->body ?? '')
            ->plugins([FontSizeRichContentPlugin::make()])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->toHtml();

        return BlogBodyImages::renderResponsive($html);
    }

    public function coverImageUrl(): ?string
    {
        return LandingMedia::url($this->cover_image_path);
    }

    public function articleImagePath(): ?string
    {
        return LandingMedia::normalizePath(
            $this->show_card_logo && filled($this->card_image_path)
                ? $this->card_image_path
                : $this->cover_image_path,
        );
    }

    public function articleImageUrl(): ?string
    {
        return LandingMedia::url($this->articleImagePath());
    }

    public function cardImageUrl(): ?string
    {
        return LandingMedia::url($this->card_image_path ?: $this->cover_image_path);
    }

    public function hasPreparedCardImage(): bool
    {
        return filled($this->card_image_path);
    }

    public function shouldShowCardLogo(): bool
    {
        return $this->hasPreparedCardImage() && $this->show_card_logo;
    }

    public function shouldShowArticleLogo(): bool
    {
        return filled($this->cover_image_path) && $this->show_card_logo;
    }

    public function logoPosition(): string
    {
        $position = (string) $this->card_logo_position;

        return array_key_exists($position, self::LOGO_POSITIONS)
            ? $position
            : 'top-left';
    }

    public function logoPositionClass(): string
    {
        return 'blog-logo--'.$this->logoPosition();
    }

    public function publishedDate(): ?Carbon
    {
        return $this->published_at ?: $this->created_at;
    }

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            $post->slug = Str::slug($post->slug);
            $post->card_logo_position = $post->logoPosition();

            if ($post->isDirty('slug') && Schema::hasTable('blog_post_redirects')) {
                $redirectConflict = BlogPostRedirect::query()
                    ->where('slug', $post->slug)
                    ->when($post->exists, fn (Builder $query) => $query->where('blog_post_id', '!=', $post->getKey()))
                    ->exists();

                if ($redirectConflict) {
                    throw ValidationException::withMessages([
                        'slug' => 'Этот URL уже сохранён как старый адрес другой статьи.',
                    ]);
                }
            }

            $post->cover_image_path = LandingMedia::normalizePath($post->cover_image_path);
            $post->card_source_image_path = LandingMedia::normalizePath($post->card_source_image_path);
            $post->card_image_path = LandingMedia::normalizePath($post->card_image_path);
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

        static::updated(function (self $post): void {
            if (! $post->wasChanged('slug') || ! Schema::hasTable('blog_post_redirects')) {
                return;
            }

            $oldSlug = Str::slug((string) $post->getOriginal('slug'));

            BlogPostRedirect::query()
                ->where('blog_post_id', $post->getKey())
                ->where('slug', $post->slug)
                ->delete();

            if ($oldSlug === '' || $oldSlug === $post->slug) {
                return;
            }

            BlogPostRedirect::query()->updateOrCreate(
                ['slug' => $oldSlug],
                ['blog_post_id' => $post->getKey()],
            );
        });
    }
}
