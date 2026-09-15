<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityComment extends Model
{
    use SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUS_DELETED = 'deleted';

    public const STATUS_LABELS = [
        self::STATUS_PUBLISHED => 'Опубликован',
        self::STATUS_HIDDEN => 'Скрыт',
        self::STATUS_DELETED => 'Удалён',
    ];

    protected $fillable = [
        'community_post_id', 'community_user_id', 'parent_id', 'root_id', 'depth',
        'body_markdown', 'body_html', 'status', 'score', 'edited_at',
    ];

    protected function casts(): array
    {
        return ['edited_at' => 'datetime'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id')->withTrashed();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(CommunityUser::class, 'community_user_id')->withTrashed();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderByDesc('score')->orderBy('created_at');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CommunityCommentVote::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(CommunityPhoto::class)->orderBy('position')->orderBy('id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(CommunityReport::class, 'target_id')
            ->where('target_type', 'comment');
    }

    public function openReports(): HasMany
    {
        return $this->reports()->where('status', 'open');
    }

    public function moderationActions(): HasMany
    {
        return $this->hasMany(CommunityModerationAction::class, 'target_id')
            ->where('target_type', 'comment');
    }

    public function getUrl(): ?string
    {
        $postUrl = $this->post?->getUrl();

        return $postUrl === null ? null : $postUrl.'#comment-'.$this->id;
    }
}
