<?php

namespace App\Models;

use App\Support\LandingIcons;
use App\Support\LandingMedia;
use App\Support\LandingSectionAnchor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingSection extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'anchor',
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
            $section->badge_icon = LandingIcons::normalize($section->badge_icon);
            $section->anchor = LandingSectionAnchor::normalize($section->anchor);

            if (! is_array($section->extra)) {
                return;
            }

            $extra = LandingIcons::normalizeExtraIcons($section->extra);

            foreach (['dashboard_image', 'mobile_image'] as $key) {
                if (array_key_exists($key, $extra)) {
                    unset($extra[$key]);
                }
            }

            if (isset($extra['carousel_slides']) && is_array($extra['carousel_slides'])) {
                $extra['carousel_slides'] = collect($extra['carousel_slides'])
                    ->map(function ($slide): ?array {
                        if (! is_array($slide)) {
                            return null;
                        }

                        $image = \App\Support\LandingHeroCarouselForm::persistImage($slide['image'] ?? null)
                            ?? LandingMedia::normalizePath($slide['image'] ?? null);

                        if ($image === null) {
                            return null;
                        }

                        return [
                            'image' => $image,
                            'alt' => trim((string) ($slide['alt'] ?? '')),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();
            }

            $delayMs = (int) ($extra['carousel_delay_ms'] ?? 5000);
            $extra['carousel_delay_ms'] = max(2000, min(60_000, $delayMs > 0 ? $delayMs : 5000));

            if (! empty($extra['carousel_slides'])) {
                $section->dashboard_image = $extra['carousel_slides'][0]['image'];
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

    public function anchorId(): ?string
    {
        return LandingSectionAnchor::id($this);
    }

    public function anchorLink(): ?string
    {
        return LandingSectionAnchor::hash($this);
    }
}
