<?php

namespace App\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final class LandingMedia
{
    public static function url(string|array|null $path): ?string
    {
        $path = self::normalizePath($path);

        if ($path === null) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $url = $disk->url($path);
            $relative = parse_url($url, PHP_URL_PATH);

            if (is_string($relative) && $relative !== '' && str_starts_with($relative, '/storage/')) {
                return $relative;
            }
        }

        return '/storage/'.ltrim($path, '/');
    }

    public static function normalizePath(string|array|null $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (is_array($path)) {
            $path = collect($path)
                ->flatten()
                ->filter(fn ($value) => filled($value) && is_string($value))
                ->first();
        }

        if (! is_string($path)) {
            return null;
        }

        $path = trim($path);

        return $path === '' ? null : $path;
    }
}
