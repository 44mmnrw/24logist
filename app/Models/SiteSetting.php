<?php

namespace App\Models;

use App\Support\LandingMedia;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'favicon_path',
        'apple_touch_icon_path',
        'og_title',
        'og_description',
        'og_image_path',
        'yandex_metrika_enabled',
        'yandex_metrika_counter_id',
        'yandex_metrika_webvisor',
        'yandex_metrika_clickmap',
        'yandex_metrika_track_links',
        'yandex_metrika_accurate_track_bounce',
    ];

    protected function casts(): array
    {
        return [
            'yandex_metrika_enabled' => 'boolean',
            'yandex_metrika_webvisor' => 'boolean',
            'yandex_metrika_clickmap' => 'boolean',
            'yandex_metrika_track_links' => 'boolean',
            'yandex_metrika_accurate_track_bounce' => 'boolean',
        ];
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'yandex_metrika_enabled' => false,
                'yandex_metrika_webvisor' => true,
                'yandex_metrika_clickmap' => true,
                'yandex_metrika_track_links' => true,
                'yandex_metrika_accurate_track_bounce' => true,
            ],
        );
    }

    protected static function booted(): void
    {
        static::saving(function (self $settings): void {
            $settings->favicon_path = LandingMedia::normalizePath($settings->favicon_path);
            $settings->apple_touch_icon_path = LandingMedia::normalizePath($settings->apple_touch_icon_path);
            $settings->og_image_path = LandingMedia::normalizePath($settings->og_image_path);
        });
    }
}
