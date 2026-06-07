<?php

namespace App\Support;

use App\Models\CmsPage;
use App\Services\LandingPageService;
use App\Services\SiteSettingsService;

final class OpenGraph
{
    public const SITE_NAME = 'ЛогистРу';

    /**
     * @return array{
     *     title: string,
     *     description: ?string,
     *     url: string,
     *     image: ?string,
     *     type: string,
     *     site_name: string,
     *     locale: string
     * }
     */
    public static function forLanding(?LandingPageService $landing = null): array
    {
        $settings = app(SiteSettingsService::class)->get();
        $hero = $landing?->section('hero');

        $title = filled($settings->og_title)
            ? (string) $settings->og_title
            : self::joinTitle($hero?->title ?: self::SITE_NAME);

        $description = filled($settings->og_description)
            ? (string) $settings->og_description
            : self::trimDescription($hero?->subtitle ?: $hero?->description);

        return self::build(
            title: $title,
            description: $description,
            url: self::absoluteUrl('/'),
            imagePath: filled($settings->og_image_path)
                ? $settings->og_image_path
                : self::defaultHeroImagePath(),
        );
    }

    /**
     * @return array{
     *     title: string,
     *     description: ?string,
     *     url: string,
     *     image: ?string,
     *     type: string,
     *     site_name: string,
     *     locale: string
     * }
     */
    public static function forPage(CmsPage $page): array
    {
        $settings = app(SiteSettingsService::class)->get();
        $extra = is_array($page->extra) ? $page->extra : [];

        $title = filled($extra['og_title'] ?? null)
            ? (string) $extra['og_title']
            : self::joinTitle($page->displayTitle());

        $description = filled($extra['og_description'] ?? null)
            ? (string) $extra['og_description']
            : self::trimDescription($page->meta_description);

        $url = $page->slug === 'privacy-policy'
            ? route('legal.privacy_policy')
            : route('pages.show', $page->slug);

        $imagePath = filled($extra['og_image_path'] ?? null)
            ? (string) $extra['og_image_path']
            : $settings->og_image_path;

        return self::build(
            title: $title,
            description: $description,
            url: $url,
            imagePath: $imagePath,
        );
    }

    /**
     * @return array{
     *     title: string,
     *     description: ?string,
     *     url: string,
     *     image: ?string,
     *     image_width: ?int,
     *     image_height: ?int,
     *     image_type: ?string,
     *     type: string,
     *     site_name: string,
     *     locale: string
     * }
     */
    private static function build(string $title, ?string $description, string $url, mixed $imagePath): array
    {
        $imageUrl = self::absoluteImageUrl($imagePath);
        $imageMeta = self::imageMeta($imagePath, $imageUrl);

        return [
            'title' => $title,
            'description' => $description,
            'url' => self::absoluteUrl($url),
            'image' => $imageUrl,
            'image_width' => $imageMeta['width'],
            'image_height' => $imageMeta['height'],
            'image_type' => $imageMeta['type'],
            'type' => 'website',
            'site_name' => self::SITE_NAME,
            'locale' => 'ru_RU',
        ];
    }

    private static function joinTitle(string $title): string
    {
        $title = trim($title);

        if ($title === '' || str_contains($title, self::SITE_NAME)) {
            return $title !== '' ? $title : self::SITE_NAME;
        }

        return $title.' — '.self::SITE_NAME;
    }

    private static function trimDescription(?string $value): ?string
    {
        $value = trim(strip_tags((string) $value));

        if ($value === '') {
            return null;
        }

        return mb_strlen($value) > 300 ? mb_substr($value, 0, 297).'…' : $value;
    }

    private static function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

    private static function absoluteImageUrl(mixed $path): ?string
    {
        $path = LandingMedia::normalizePath($path);

        if ($path === null) {
            $hero = app(LandingPageService::class)->section('hero');
            $slides = $hero !== null ? LandingHeroCarousel::slides($hero) : [];

            if ($slides !== [] && filled($slides[0]['url'] ?? null)) {
                return self::absoluteUrl((string) $slides[0]['url']);
            }

            return null;
        }

        if ($path === OpenGraphHeroCard::defaultImagePath() && ! OpenGraphHeroCard::defaultImageExists()) {
            return self::absoluteImageUrl(null);
        }

        $url = LandingMedia::url($path);

        if ($url === null) {
            return null;
        }

        return self::absoluteUrl($url);
    }

    /**
     * @return array{width: ?int, height: ?int, type: ?string}
     */
    private static function imageMeta(mixed $imagePath, ?string $imageUrl): array
    {
        $file = self::resolveImageFile($imagePath);

        if ($file === null || ! is_file($file)) {
            return ['width' => null, 'height' => null, 'type' => null];
        }

        $info = @getimagesize($file);

        if ($info === false) {
            return ['width' => null, 'height' => null, 'type' => null];
        }

        $mime = $info['mime'] ?? null;

        if (! is_string($mime) || ! in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            $mime = null;
        }

        return [
            'width' => isset($info[0]) ? (int) $info[0] : null,
            'height' => isset($info[1]) ? (int) $info[1] : null,
            'type' => $mime,
        ];
    }

    private static function resolveImageFile(mixed $imagePath): ?string
    {
        $path = LandingMedia::normalizePath($imagePath);

        if ($path === null) {
            return null;
        }

        if (str_starts_with($path, 'images/')) {
            $file = public_path($path);

            return is_file($file) ? $file : null;
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        }

        return null;
    }

    private static function defaultHeroImagePath(): ?string
    {
        if (OpenGraphHeroCard::defaultImageExists()) {
            return OpenGraphHeroCard::defaultImagePath();
        }

        return null;
    }
}
