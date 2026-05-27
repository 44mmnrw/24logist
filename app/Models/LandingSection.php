<?php

namespace App\Models;

use App\Support\LandingMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingSection extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'kicker',
        'title',
        'subtitle',
        'description',
        'badge_text',
        'badge_icon',
        'button_primary_text',
        'button_primary_url',
        'button_secondary_text',
        'button_secondary_url',
        'dashboard_image',
        'mobile_image',
        'extra',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'extra' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $section): void {
            $section->dashboard_image = LandingMedia::normalizePath($section->dashboard_image);
            $section->mobile_image = LandingMedia::normalizePath($section->mobile_image);

            if (! is_array($section->extra)) {
                return;
            }

            $extra = $section->extra;

            foreach (['dashboard_image', 'mobile_image'] as $key) {
                if (array_key_exists($key, $extra)) {
                    unset($extra[$key]);
                }
            }

            $section->extra = $extra;
        });
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(LandingBlock::class, 'section_slug', 'slug')
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function allBlocks(): HasMany
    {
        return $this->hasMany(LandingBlock::class, 'section_slug', 'slug')
            ->orderBy('sort_order');
    }
}
