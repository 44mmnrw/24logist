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
        'seo_meta_title',
        'seo_keywords',
        'org_brand_name',
        'org_legal_name',
        'org_email',
        'org_phone',
        'org_logo_path',
        'org_street_address',
        'org_address_locality',
        'org_address_region',
        'org_postal_code',
        'org_address_country',
        'org_inn',
        'org_ogrn',
        'org_same_as',
        'twitter_site',
        'twitter_creator',
        'google_site_verification',
        'yandex_site_verification',
        'ai_site_summary',
        'llms_txt_extra',
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
                'org_brand_name' => 'ЛогистРу',
                'org_legal_name' => 'Общество с ограниченной ответственностью «Энерви Групп»',
                'org_email' => 'info@24logist.ru',
                'org_phone' => '+7 (495) 109-25-44',
                'org_street_address' => 'ул. Мира, д. 4, помещ. 3',
                'org_address_locality' => 'Подольск',
                'org_address_region' => 'Московская область',
                'org_postal_code' => '142103',
                'org_address_country' => 'RU',
                'org_inn' => '5074081476',
                'org_ogrn' => '1235000051824',
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
            $settings->org_logo_path = LandingMedia::normalizePath($settings->org_logo_path);
        });
    }
}
