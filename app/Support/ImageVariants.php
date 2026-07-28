<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class ImageVariants
{
    /**
     * @return array{
     *     url: ?string,
     *     avif_srcset: ?string,
     *     webp_srcset: ?string
     * }
     */
    public static function data(string|array|null $path): array
    {
        $path = LandingMedia::normalizePath($path);

        return [
            'url' => $path !== null ? LandingMedia::url($path) : null,
            'avif_srcset' => $path !== null ? self::srcset($path, 'avif') : null,
            'webp_srcset' => $path !== null ? self::srcset($path, 'webp') : null,
        ];
    }

    public static function srcset(string $sourcePath, string $format): ?string
    {
        $variants = self::for($sourcePath, $format);

        if ($variants === []) {
            return null;
        }

        return collect($variants)
            ->map(fn (string $path, int $width): string => LandingMedia::url($path).' '.$width.'w')
            ->implode(', ');
    }

    /**
     * @return array<int, string>
     */
    public static function for(string $sourcePath, string $format): array
    {
        $sourcePath = LandingMedia::normalizePath($sourcePath);
        $format = strtolower($format);

        if (
            $sourcePath === null
            || ! in_array($format, ['avif', 'webp'], true)
            || str_starts_with($sourcePath, 'http://')
            || str_starts_with($sourcePath, 'https://')
            || str_starts_with($sourcePath, '/')
        ) {
            return [];
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($sourcePath)) {
            return [];
        }

        $directory = pathinfo($sourcePath, PATHINFO_DIRNAME);
        $directory = $directory === '.' ? '' : $directory;
        $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
        $pattern = '/^'.preg_quote($baseName, '/').'--([1-9][0-9]*)w\.'.preg_quote($format, '/').'$/i';
        $variants = [];

        foreach ($disk->files($directory) as $path) {
            if (! preg_match($pattern, basename($path), $matches)) {
                continue;
            }

            $variants[(int) $matches[1]] = $path;
        }

        ksort($variants, SORT_NUMERIC);

        return $variants;
    }

    public static function isVariantPath(string $path): bool
    {
        return preg_match('/--[1-9][0-9]*w\.(avif|webp)$/i', $path) === 1;
    }

    public static function isOptimizableOriginal(string $path): bool
    {
        if (self::isVariantPath($path)) {
            return false;
        }

        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), [
            'avif',
            'jpeg',
            'jpg',
            'png',
            'webp',
        ], true);
    }
}
