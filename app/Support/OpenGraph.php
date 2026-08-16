<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\CmsPage;
use App\Services\LandingPageService;
use App\Services\SiteSettingsService;

final class OpenGraph
{
    public const SITE_NAME = 'ЛогистРу';

    public const ROBOTS_INDEX = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';

    public const ROBOTS_NOINDEX = 'noindex, nofollow';

    /**
     * @return array<string, mixed>
     */
    public static function forLanding(?LandingPageService $landing = null): array
    {
        $settings = app(SiteSettingsService::class)->get();
        $hero = $landing?->section('hero');

        $title = filled($settings->seo_meta_title)
            ? (string) $settings->seo_meta_title
            : (filled($settings->og_title)
                ? (string) $settings->og_title
                : self::joinTitle($hero?->title ?: self::SITE_NAME));

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
            type: 'website',
            robots: self::ROBOTS_INDEX,
            keywords: $settings->seo_keywords,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function forNotFound(): array
    {
        return self::build(
            title: self::joinTitle('Страница не найдена'),
            description: 'Запрошенный адрес не существует или был перемещён. Вернитесь на главную — там все маршруты на месте.',
            url: url()->current(),
            imagePath: self::defaultHeroImagePath(),
            type: 'website',
            robots: self::ROBOTS_NOINDEX,
        );
    }

    /**
     * @return array<string, mixed>
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

        $url = filled($extra['canonical_url'] ?? null)
            ? (string) $extra['canonical_url']
            : ($page->slug === 'privacy-policy'
                ? route('legal.privacy_policy')
                : route('pages.show', $page->slug));

        $imagePath = filled($extra['og_image_path'] ?? null)
            ? (string) $extra['og_image_path']
            : $settings->og_image_path;

        $robots = filled($extra['meta_robots'] ?? null)
            ? (string) $extra['meta_robots']
            : self::ROBOTS_INDEX;

        $type = filled($extra['og_type'] ?? null)
            ? (string) $extra['og_type']
            : 'website';

        return self::build(
            title: $title,
            description: $description,
            url: $url,
            imagePath: $imagePath,
            type: $type,
            robots: $robots,
            keywords: $extra['meta_keywords'] ?? $settings->seo_keywords,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function forBlogIndex(): array
    {
        $settings = app(SiteSettingsService::class)->get();
        $title = filled($settings->blog_title)
            ? (string) $settings->blog_title
            : 'Блог о цифровой логистике';

        $description = filled($settings->blog_description)
            ? (string) $settings->blog_description
            : 'Материалы 24Logist о перевозках, автоматизации логистики, документообороте, контроле рейсов и управлении автопарком.';

        return self::build(
            title: self::joinTitle($title),
            description: $description,
            url: route('blog.index'),
            imagePath: $settings->og_image_path ?: self::defaultHeroImagePath(),
            type: 'website',
            robots: self::ROBOTS_INDEX,
            keywords: 'логистика, грузоперевозки, автоматизация логистики, TMS, управление автопарком',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function forBlogTag(BlogTag $tag): array
    {
        $settings = app(SiteSettingsService::class)->get();
        $pageTitle = self::joinTitle($tag->meta_title ?: 'Статьи с тегом «'.$tag->name.'»');
        $title = filled($tag->og_title) ? (string) $tag->og_title : $pageTitle;

        $description = filled($tag->og_description)
            ? (string) $tag->og_description
            : ($tag->meta_description ?: $tag->description ?: 'Материалы блога 24Logist по теме «'.$tag->name.'».');

        $imagePath = $tag->og_image_path ?: $settings->og_image_path;

        $meta = self::build(
            title: $title,
            description: $description,
            url: filled($tag->canonical_url) ? (string) $tag->canonical_url : $tag->getUrl(),
            imagePath: $imagePath ?: self::defaultHeroImagePath(),
            type: $tag->og_type ?: 'website',
            robots: filled($tag->meta_robots) ? (string) $tag->meta_robots : self::ROBOTS_INDEX,
            keywords: $tag->meta_keywords ?: $tag->name,
        );

        $meta['twitter_card'] = $tag->twitter_card ?: 'summary_large_image';
        $meta['html_title'] = $pageTitle;
        $meta['twitter_title'] = $tag->twitter_title ?: $meta['title'];
        $meta['twitter_description'] = $tag->twitter_description ?: $meta['description'];
        $meta['twitter_image'] = self::absoluteImageUrl($tag->twitter_image_path ?: $imagePath) ?? $meta['image'];

        return $meta;
    }

    /**
     * @return array<string, mixed>
     */
    public static function forBlogPost(BlogPost $post): array
    {
        $settings = app(SiteSettingsService::class)->get();

        $title = filled($post->og_title)
            ? (string) $post->og_title
            : self::joinTitle($post->displayTitle());

        $description = filled($post->og_description)
            ? (string) $post->og_description
            : self::trimDescription($post->meta_description ?: $post->displayExcerpt());

        $imagePath = filled($post->og_image_path)
            ? $post->og_image_path
            : ($post->cover_image_path ?: $settings->og_image_path);

        $meta = self::build(
            title: $title,
            description: $description,
            url: filled($post->canonical_url) ? (string) $post->canonical_url : $post->getUrl(),
            imagePath: $imagePath,
            type: $post->og_type ?: 'article',
            robots: filled($post->meta_robots) ? (string) $post->meta_robots : self::ROBOTS_INDEX,
            keywords: $post->meta_keywords ?: $settings->seo_keywords,
        );

        $twitterImagePath = filled($post->twitter_image_path)
            ? $post->twitter_image_path
            : $imagePath;

        $meta['twitter_card'] = filled($post->twitter_card)
            ? (string) $post->twitter_card
            : (filled($meta['image']) ? 'summary_large_image' : 'summary');

        $meta['twitter_title'] = filled($post->twitter_title)
            ? (string) $post->twitter_title
            : $meta['title'];

        $meta['twitter_description'] = filled($post->twitter_description)
            ? self::trimDescription($post->twitter_description)
            : $meta['description'];

        $meta['twitter_image'] = self::absoluteImageUrl($twitterImagePath) ?? $meta['image'];

        return $meta;
    }

    public static function absolutePublicUrl(mixed $path): ?string
    {
        $path = LandingMedia::normalizePath($path);

        if ($path === null) {
            return null;
        }

        if ($path === OpenGraphHeroCard::defaultImagePath() && ! OpenGraphHeroCard::defaultImageExists()) {
            return null;
        }

        $url = LandingMedia::url($path);

        return $url !== null ? self::absoluteUrl($url) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function build(
        string $title,
        ?string $description,
        string $url,
        mixed $imagePath,
        string $type = 'website',
        ?string $robots = self::ROBOTS_INDEX,
        mixed $keywords = null,
    ): array {
        $settings = app(SiteSettingsService::class)->get();
        $imageUrl = self::absoluteImageUrl($imagePath);
        $imageMeta = self::imageMeta($imagePath, $imageUrl);

        $meta = [
            'title' => $title,
            'description' => $description,
            'url' => self::absoluteUrl($url),
            'image' => $imageUrl,
            'image_width' => $imageMeta['width'],
            'image_height' => $imageMeta['height'],
            'image_type' => $imageMeta['type'],
            'type' => $type,
            'site_name' => filled($settings->org_brand_name) ? (string) $settings->org_brand_name : self::SITE_NAME,
            'locale' => 'ru_RU',
            'robots' => $robots,
            'twitter_site' => self::normalizeTwitterHandle($settings->twitter_site),
            'twitter_creator' => self::normalizeTwitterHandle($settings->twitter_creator),
            'google_site_verification' => filled($settings->google_site_verification)
                ? (string) $settings->google_site_verification
                : null,
            'yandex_site_verification' => filled($settings->yandex_site_verification)
                ? (string) $settings->yandex_site_verification
                : null,
            'keywords' => self::normalizeKeywords($keywords),
            'author' => filled($settings->org_legal_name)
                ? (string) $settings->org_legal_name
                : (filled($settings->org_brand_name) ? (string) $settings->org_brand_name : self::SITE_NAME),
        ];

        if (filled($settings->ai_site_summary)) {
            $meta['ai_summary'] = trim((string) $settings->ai_site_summary);
        }

        return $meta;
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

    private static function normalizeKeywords(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $value = trim(strip_tags((string) $value));

        return $value !== '' ? $value : null;
    }

    private static function normalizeTwitterHandle(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return str_starts_with($value, '@') ? $value : '@'.$value;
    }

    public static function absoluteUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($url, '/');
    }

    private static function absoluteImageUrl(mixed $path): ?string
    {
        return self::absolutePublicUrl($path) ?? self::fallbackCarouselImage();
    }

    private static function fallbackCarouselImage(): ?string
    {
        $hero = app(LandingPageService::class)->section('hero');
        $slides = $hero !== null ? LandingHeroCarousel::slides($hero) : [];

        if ($slides !== [] && filled($slides[0]['url'] ?? null)) {
            return self::absoluteUrl((string) $slides[0]['url']);
        }

        return null;
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
