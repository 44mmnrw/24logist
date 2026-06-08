<?php

namespace App\Support;

use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Storage;

final class SiteIconRasterizer
{
    /**
     * @return array{path: string, mtime: int}|null
     */
    public static function sourceFromSettings(): ?array
    {
        $settings = app(SiteSettingsService::class)->get();
        $applePath = LandingMedia::normalizePath($settings->apple_touch_icon_path ?? null);
        $faviconPath = LandingMedia::normalizePath($settings->favicon_path);

        return self::resolveRasterSource($applePath)
            ?? self::resolveRasterSource($faviconPath);
    }

    public static function fallbackPath(): ?string
    {
        $path = public_path('images/apple-touch-icon.png');

        return is_file($path) ? $path : null;
    }

    /**
     * @return array{path: string, mtime: int}|null
     */
    public static function resolveRasterSource(?string $path): ?array
    {
        if ($path === null) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico'], true)) {
            return null;
        }

        if (str_starts_with($path, 'images/')) {
            $file = public_path($path);
        } elseif (Storage::disk('public')->exists($path)) {
            $file = Storage::disk('public')->path($path);
        } else {
            return null;
        }

        if (! is_file($file)) {
            return null;
        }

        return [
            'path' => $file,
            'mtime' => (int) filemtime($file),
        ];
    }

    public static function rasterizeToSquare(string $sourcePath, string $destPath, int $size): bool
    {
        $image = self::loadImage($sourcePath);

        if ($image === null) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $cropSize = min($width, $height);
        $srcX = (int) floor(($width - $cropSize) / 2);
        $srcY = (int) floor(($height - $cropSize) / 2);

        $target = imagecreatetruecolor($size, $size);

        if ($target === false) {
            imagedestroy($image);

            return false;
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $size, $size, $transparent);

        imagecopyresampled(
            $target,
            $image,
            0,
            0,
            $srcX,
            $srcY,
            $size,
            $size,
            $cropSize,
            $cropSize,
        );

        $saved = imagepng($target, $destPath);

        imagedestroy($image);
        imagedestroy($target);

        return $saved;
    }

    public static function loadImage(string $path): ?\GdImage
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $image = match ($extension) {
            'png' => @imagecreatefrompng($path),
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'gif' => @imagecreatefromgif($path),
            'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            'ico' => @imagecreatefromstring((string) file_get_contents($path)),
            default => false,
        };

        return $image instanceof \GdImage ? $image : null;
    }
}
