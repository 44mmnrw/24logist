<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

final class PwaIcons
{
    /** @var list<int> */
    public const SIZES = [192, 512];

    public static function cachePath(int $size): string
    {
        return storage_path('app/cache/pwa-icon-'.$size.'.png');
    }

    public static function url(int $size): string
    {
        return rtrim((string) config('app.url'), '/').'/icons/icon-'.$size.'.png';
    }

    public static function ensureCached(int $size): ?string
    {
        if (! in_array($size, self::SIZES, true)) {
            return null;
        }

        if (! extension_loaded('gd')) {
            return self::fallbackPath($size);
        }

        $source = SiteIconRasterizer::sourceFromSettings();
        $cache = self::cachePath($size);
        $sourceMtime = $source['mtime'] ?? 0;

        if (is_file($cache) && filemtime($cache) >= $sourceMtime) {
            return $cache;
        }

        File::ensureDirectoryExists(dirname($cache));

        if ($source !== null && SiteIconRasterizer::rasterizeToSquare($source['path'], $cache, $size)) {
            return $cache;
        }

        $fallback = self::fallbackPath($size);

        if ($fallback !== null) {
            copy($fallback, $cache);

            return $cache;
        }

        $appleCache = AppleTouchIcon::ensureCached();

        if ($appleCache !== null && SiteIconRasterizer::rasterizeToSquare($appleCache, $cache, $size)) {
            return $cache;
        }

        return null;
    }

    private static function fallbackPath(int $size): ?string
    {
        $path = public_path('images/icon-'.$size.'.png');

        return is_file($path) ? $path : null;
    }
}
