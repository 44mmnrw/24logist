<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Support\LandingMedia;
use Illuminate\Support\Facades\Cache;

class SiteSettingsService
{
    private const CACHE_KEY = 'site.settings.v3';

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
        Cache::forget('site.settings.v2');
        Cache::forget('site.settings.v1');
    }

    /**
     * @return array{url: string, type: string}
     */
    public function favicon(): array
    {
        $path = LandingMedia::normalizePath($this->get()->favicon_path);

        if ($path === null) {
            return [
                'url' => asset('images/logo.svg'),
                'type' => 'image/svg+xml',
            ];
        }

        $url = LandingMedia::url($path) ?? asset('images/logo.svg');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $type = match ($extension) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'ico' => 'image/x-icon',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return [
            'url' => $url,
            'type' => $type,
        ];
    }
}
