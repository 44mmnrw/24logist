<?php

namespace App\Support;

use App\Models\CmsPage;

final class LandingLinks
{
    public static function resolve(?string $link): ?string
    {
        $link = trim((string) ($link ?? ''));

        if ($link === '') {
            return null;
        }

        if (str_starts_with($link, '#')) {
            if (request()->is('/')) {
                return $link;
            }

            return url('/').$link;
        }

        if (str_starts_with($link, '/pages/')) {
            $slug = CmsPage::normalizeSlug($link);

            return $slug !== '' ? route('pages.show', $slug) : $link;
        }

        $host = parse_url($link, PHP_URL_HOST);
        $path = parse_url($link, PHP_URL_PATH);

        if (
            is_string($host)
            && is_string($path)
            && str_starts_with($path, '/pages/')
            && self::isLocalHost($host)
        ) {
            $slug = CmsPage::normalizeSlug($path);

            return $slug !== '' ? route('pages.show', $slug) : $link;
        }

        if (
            str_starts_with($link, '/')
            || str_starts_with($link, '//')
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $link)
        ) {
            return $link;
        }

        $slug = CmsPage::normalizeSlug($link);

        if (
            $slug !== ''
            && CmsPage::query()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->exists()
        ) {
            return route('pages.show', $slug);
        }

        return $link;
    }

    private static function isLocalHost(string $host): bool
    {
        $host = strtolower($host);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = request()->getHost();

        return in_array($host, array_filter([
            is_string($appHost) ? strtolower($appHost) : null,
            is_string($requestHost) ? strtolower($requestHost) : null,
        ]), true);
    }
}
