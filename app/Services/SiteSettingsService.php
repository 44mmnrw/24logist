<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

class SiteSettingsService
{
    private const CACHE_KEY = 'site.settings.v2';

    public function get(): SiteSetting
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return (new SiteSetting)->newFromBuilder($cached);
        }

        $settings = SiteSetting::instance();

        Cache::put(self::CACHE_KEY, $settings->getAttributes(), now()->addHour());

        return $settings;
    }

    /**
     * @return array{
     *     counter_id: int,
     *     webvisor: bool,
     *     clickmap: bool,
     *     track_links: bool,
     *     accurate_track_bounce: bool
     * }|null
     */
    public function yandexMetrika(): ?array
    {
        $settings = $this->get();

        if (! $settings->yandex_metrika_enabled) {
            return null;
        }

        $counterId = preg_replace('/\D+/', '', (string) $settings->yandex_metrika_counter_id);

        if ($counterId === null || $counterId === '') {
            return null;
        }

        return [
            'counter_id' => (int) $counterId,
            'webvisor' => (bool) $settings->yandex_metrika_webvisor,
            'clickmap' => (bool) $settings->yandex_metrika_clickmap,
            'track_links' => (bool) $settings->yandex_metrika_track_links,
            'accurate_track_bounce' => (bool) $settings->yandex_metrika_accurate_track_bounce,
        ];
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('site.settings.v1');
    }
}
