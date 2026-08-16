<?php

namespace App\Models;

use App\Support\LandingIcons;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
