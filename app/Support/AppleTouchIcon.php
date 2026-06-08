<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

final class AppleTouchIcon
{
    public const SIZE = 180;

    public static function cachePath(): string
    {
        return storage_path('app/cache/apple-touch-icon.png');
    }

    public static function ensureCached(): ?string
    {
        if (! extension_loaded('gd')) {
            return self::fallbackPath();
        }

        $source = SiteIconRasterizer::sourceFromSettings();
        $cache = self::cachePath();
        $sourceMtime = $source['mtime'] ?? 0;

        if (is_file($cache) && filemtime($cache) >= $sourceMtime) {
            return $cache;
        }

        File::ensureDirectoryExists(dirname($cache));

        if ($source !== null && SiteIconRasterizer::rasterizeToSquare($source['path'], $cache, self::SIZE)) {
            return $cache;
        }

        $fallback = self::fallbackPath();

        if ($fallback !== null) {
            copy($fallback, $cache);

            return $cache;
        }

        return null;
    }

    public static function url(): string
    {
        return rtrim((string) config('app.url'), '/').'/apple-touch-icon.png';
    }

    private static function fallbackPath(): ?string
    {
        $path = public_path('images/apple-touch-icon.png');

        return is_file($path) ? $path : null;
    }
}
