<?php

namespace App\Models;

use App\Services\Community\CommunityPostSeoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityPost extends Model
{
    use SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_DELETED = 'deleted';

    public const STATUS_LABELS = [
        self::STATUS_PUBLISHED => 'Опубликована',
        self::STATUS_HIDDEN => 'Скрыта',
        self::STATUS_DELETED => 'Удалена',
    ];

    protected $fillable = [
        'community_user_id', 'community_category_id', 'slug', 'title', 'body_markdown',
        'body_html', 'external_url', 'status', 'score', 'comments_count', 'hot_score',
        'is_pinned', 'locked_at', 'edited_at', 'published_at', 'accepted_comment_id', 'resolved_at',
        'meta_title', 'meta_description', 'meta_keywords', 'meta_robots', 'canonical_url',
        'og_title', 'og_description', 'og_type', 'twitter_title', 'twitter_description',
        'twitter_card', 'seo_is_custom',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'accepted_comment_id' => 'integer',
            'locked_at' => 'datetime',
            'edited_at' => 'datetime',
            'published_at' => 'datetime',
            'resolved_at' => 'datetime',
            'hot_score' => 'float',
            'seo_is_custom' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class, 'community_user_id')->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CommunityCategory::class, 'community_category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CommunityPhoto::class)->orderBy('position')->orderBy('id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CommunityPostVote::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityReport::class, 'target_id')
            ->where('target_type', 'post');
    }

    public function openReports(): HasMany
    {
        return $this->reports()->where('status', 'open');
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(CommunityModerationAction::class, 'target_id')
            ->where('target_type', 'post');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)->whereNull('deleted_at');
    }

    public function getUrl(): string
    {
        return route('community.posts.show', ['post' => $this->id, 'slug' => $this->slug]);
    }

    protected static function booted(): void
    {
        static::creating(function (self $post): void {
            app(CommunityPostSeoService::class)->fill($post);
        });

        static::updating(function (self $post): void {
            if ($post->isDirty('slug') && ! $post->isDirty('canonical_url')) {
                $previousUrl = route('community.posts.show', ['post' => $post->id, 'slug' => $post->getOriginal('slug')]);
                if (blank($post->canonical_url) || $post->canonical_url === $previousUrl) {
                    $post->canonical_url = $post->getUrl();
                }
            }

            if (! $post->seo_is_custom && $post->isDirty([
                'title', 'body_markdown', 'body_html', 'external_url', 'community_category_id',
            ])) {
                app(CommunityPostSeoService::class)->fill($post, overwrite: true);
                $post->canonical_url = $post->getUrl();
            }
        });

        static::created(function (self $post): void {
            if (blank($post->canonical_url)) {
                $post->forceFill(['canonical_url' => $post->getUrl()])->saveQuietly();
            }
        });
    }
}
