<?php

namespace App\Support;

use App\Services\LandingPageService;

final class OpenGraphHeroCard
{
    /**
     * @return array{
     *     badge: ?string,
     *     title: string,
     *     subtitle: ?string,
     *     bullets: list<string>,
     *     primary_button: ?string,
     *     secondary_button: ?string,
     *     hint: ?string,
     *     image_url: ?string,
     *     brand: string
     * }
     */
    public static function data(?LandingPageService $landing = null): array
    {
        $landing ??= app(LandingPageService::class);
        $hero = $landing->section('hero');
        $extra = is_array($hero?->extra) ? $hero->extra : [];

        $imageUrl = null;
        $slides = $hero !== null ? LandingHeroCarousel::slides($hero) : [];

        if ($slides !== []) {
            $imageUrl = self::absoluteAssetUrl($slides[0]['url'] ?? null);
        }

        $bullets = $landing->blocks('hero', 'bullet')
            ->take(4)
            ->pluck('title')
            ->filter()
            ->values()
            ->all();

        return [
            'badge' => filled($hero?->badge_text) ? (string) $hero->badge_text : null,
            'title' => filled($hero?->title) ? (string) $hero->title : OpenGraph::SITE_NAME,
            'subtitle' => filled($hero?->subtitle) ? (string) $hero->subtitle : null,
            'bullets' => $bullets,
            'primary_button' => filled($hero?->button_primary_text) ? (string) $hero->button_primary_text : null,
            'secondary_button' => filled($hero?->button_secondary_text) ? (string) $hero->button_secondary_text : null,
            'hint' => filled($extra['hint_text'] ?? null) ? (string) $extra['hint_text'] : null,
            'image_url' => $imageUrl,
            'brand' => OpenGraph::SITE_NAME,
        ];
    }

    public static function defaultImagePath(): string
    {
        return 'images/og-hero.png';
    }

    public static function defaultImageExists(): bool
    {
        return is_file(public_path(self::defaultImagePath()));
    }

    /**
     * @return array<string, mixed>
     */
    public static function dataForPng(?LandingPageService $landing = null): array
    {
        $data = self::data($landing);

        if (filled($data['image_url'] ?? null)) {
            $embedded = self::embedLocalImage((string) $data['image_url']);

            if ($embedded !== null) {
                $data['image_url'] = $embedded;
            }
        }

        return $data;
    }

    private static function embedLocalImage(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $file = null;

        if (str_starts_with($path, '/storage/')) {
            $file = storage_path('app/public/'.ltrim(substr($path, strlen('/storage/')), '/'));
        } elseif (str_starts_with($path, '/images/')) {
            $file = public_path(ltrim($path, '/'));
        }

        if ($file === null || ! is_file($file)) {
            return null;
        }

        $mime = mime_content_type($file) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file));
    }

    private static function absoluteAssetUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }
}
