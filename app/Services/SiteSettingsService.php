<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Support\LandingMedia;
use Illuminate\Support\Facades\Cache;

class SiteSettingsService
{
    private const CACHE_KEY = 'site.settings.v20';

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

    public function googleAnalyticsMeasurementId(): ?string
    {
        $settings = $this->get();

        if (! $settings->google_analytics_enabled) {
            return null;
        }

        $measurementId = strtoupper(trim((string) $settings->google_analytics_measurement_id));

        return preg_match('/^G-[A-Z0-9]+$/', $measurementId) === 1
            ? $measurementId
            : null;
    }

    public function communityEnabled(): bool
    {
        return (bool) $this->get()->getAttribute('community_enabled');
    }

    public function communityMaxEnabled(): bool
    {
        if (! $this->communityEnabled()) {
            return false;
        }

        return (bool) $this->get()->getAttribute('community_max_enabled');
    }

    public function communityVkEnabled(): bool
    {
        return $this->communityEnabled() && (bool) $this->get()->getAttribute('community_vk_enabled');
    }

    public function vkClientId(): string
    {
        return (string) $this->get()->getAttribute('community_vk_client_id');
    }

    public function vkClientSecret(): string
    {
        return (string) $this->get()->getAttribute('community_vk_client_secret');
    }

    public function vkServiceToken(): string
    {
        return (string) $this->get()->getAttribute('community_vk_service_token');
    }

    public function vkRedirectUri(): string
    {
        return (string) $this->get()->getAttribute('community_vk_redirect_uri');
    }

    public function telegramClientId(): string
    {
        return (string) $this->get()->getAttribute('community_telegram_client_id');
    }

    public function telegramClientSecret(): string
    {
        return (string) $this->get()->getAttribute('community_telegram_client_secret');
    }

    public function telegramBotToken(): string
    {
        return (string) $this->get()->getAttribute('community_telegram_bot_token');
    }

    public function telegramRedirectUri(): string
    {
        return (string) $this->get()->getAttribute('community_telegram_redirect_uri');
    }

    public function maxBotUsername(): string
    {
        $username = trim((string) $this->get()->getAttribute('community_max_bot_username'));
        $username = (string) preg_replace('#^(?:https?://)?(?:www\.)?max\.ru/#i', '', $username);
        $username = (string) preg_replace('/[?#].*$/', '', $username);

        return trim($username, " \t\n\r\0\x0B@/");
    }

    public function maxBotToken(): string
    {
        return (string) $this->get()->getAttribute('community_max_bot_token');
    }

    public function maxWebhookSecret(): string
    {
        return (string) $this->get()->getAttribute('community_max_webhook_secret');
    }

    public function routeCalculatorEnabled(): bool
    {
        return (bool) $this->get()->getAttribute('route_calculator_enabled');
    }

    public function routeApiBaseUrl(): string
    {
        return rtrim(trim((string) $this->get()->getAttribute('route_api_base_url')), '/');
    }

    public function routeApiSecret(): string
    {
        return trim((string) $this->get()->getAttribute('route_api_secret'));
    }

    public function routeApiTimeout(): int
    {
        return min(60, max(2, (int) ($this->get()->getAttribute('route_api_timeout') ?: 15)));
    }

    public function routeApiConfigured(): bool
    {
        return $this->routeCalculatorEnabled()
            && filter_var($this->routeApiBaseUrl(), FILTER_VALIDATE_URL) !== false
            && strlen($this->routeApiSecret()) >= 32;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('site.settings.v19');
        Cache::forget('site.settings.v18');
        Cache::forget('site.settings.v17');
        Cache::forget('site.settings.v16');
        Cache::forget('site.settings.v15');
        Cache::forget('site.settings.v14');
        Cache::forget('site.settings.v13');
        Cache::forget('site.settings.v12');
        app(PublicPageCache::class)->forgetLanding();
        Cache::forget('site.settings.v11');
        Cache::forget('site.settings.v10');
        Cache::forget('site.settings.v9');
        Cache::forget('site.settings.v8');
        Cache::forget('site.settings.v7');
        Cache::forget('site.settings.v5');
        Cache::forget('site.settings.v3');
        Cache::forget('site.settings.v2');
        Cache::forget('site.settings.v1');
    }

    /**
     * @return array{url: string, root_url: string, type: string}
     */
    public function favicon(): array
    {
        $path = LandingMedia::normalizePath($this->get()->favicon_path);
        $rootUrl = $this->absoluteUrl(route('favicon', absolute: false));

        if ($path === null) {
            return [
                'url' => $this->absoluteUrl(asset('images/favicon.svg')),
                'root_url' => $rootUrl,
                'type' => 'image/svg+xml',
            ];
        }

        $url = LandingMedia::url($path) ?? asset('images/favicon.svg');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $type = match ($extension) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'ico' => 'image/x-icon',
            'gif' => 'image/gif',
            'jpeg', 'jpg' => 'image/jpeg',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return [
            'url' => $this->absoluteUrl($url),
            'root_url' => $rootUrl,
            'type' => $type,
        ];
    }

    private function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }
}
