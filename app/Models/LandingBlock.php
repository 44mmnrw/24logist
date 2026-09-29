<?php

namespace App\Models;

use App\Support\LandingIcons;
use App\Support\LandingReviews;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class LandingBlock extends Model
{
    protected $fillable = [
        'section_slug',
        'block_type',
        'parent_id',
        'title',
        'subtitle',
        'description',
        'icon',
        'price',
        'tag',
        'secondary_tag',
        'link',
        'button_text',
        'button_style',
        'extra',
        'is_active',
        'is_highlighted',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'is_active' => 'boolean',
            'is_highlighted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $block): void {
            $block->icon = LandingIcons::normalize($block->icon);

            if (is_array($block->extra)) {
                $block->extra = LandingIcons::normalizeExtraIcons($block->extra);
            }

            if ($block->block_type === 'review' && $block->is_active && ! LandingReviews::isApproved($block)) {
                throw ValidationException::withMessages([
                    'extra.publication_approved' => 'Подтвердите согласование отзыва с клиентом перед публикацией.',
                ]);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }
}
