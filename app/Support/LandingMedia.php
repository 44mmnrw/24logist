<?php

namespace App\Support;

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

        return Storage::disk('public')->url($path);
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
