<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityPost extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'community_user_id', 'community_category_id', 'slug', 'title', 'body_markdown',
        'body_html', 'external_url', 'status', 'score', 'comments_count', 'hot_score',
        'is_pinned', 'locked_at', 'edited_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'locked_at' => 'datetime',
            'edited_at' => 'datetime',
            'published_at' => 'datetime',
            'hot_score' => 'float',
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

    public function votes(): HasMany
    {
        return $this->hasMany(CommunityPostVote::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNull('deleted_at');
    }

    public function getUrl(): string
    {
        return route('community.posts.show', ['post' => $this->id, 'slug' => $this->slug]);
    }
}
