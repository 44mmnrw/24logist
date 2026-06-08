<?php

namespace App\Support;

use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Storage;

final class SiteIconRasterizer
{
    public static function brandSvgPath(): string
    {
        return public_path('images/favicon.svg');
    }

    public static function brandSvgMtime(): int
    {
        $path = self::brandSvgPath();

        return is_file($path) ? (int) filemtime($path) : 0;
    }

    /**
     * @return array{path: string, mtime: int}|null
     */
    public static function sourceFromSettings(): ?array
    {
        $settings = app(SiteSettingsService::class)->get();
        $applePath = LandingMedia::normalizePath($settings->apple_touch_icon_path ?? null);

        return self::resolveRasterSource($applePath);
    }

    public static function fallbackPath(): ?string
    {
        $path = public_path('images/apple-touch-icon.png');

        return is_file($path) ? $path : null;
    }

    public static function rasterizeBrandSvg(string $destPath, int $size): bool
    {
        $svgPath = self::brandSvgPath();

        if (! is_file($svgPath)) {
            return false;
        }

        return self::rasterizeSvgToSquare($svgPath, $destPath, $size);
    }

    public static function rasterizeSvgToSquare(string $svgPath, string $destPath, int $size): bool
    {
        if (extension_loaded('imagick')) {
            try {
                $imagick = new \Imagick;
                $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
                $imagick->readImage($svgPath);
                $imagick->setImageFormat('png');
                $imagick->resizeImage($size, $size, \Imagick::FILTER_LANCZOS, 1, true);
                $imagick->writeImage($destPath);
                $imagick->clear();

                return is_file($destPath);
            } catch (\Throwable) {
                // fall through to node/sharp
            }
        }

        $script = base_path('script_ai/rasterize-svg-icon.mjs');

        if (! is_file($script)) {
            return false;
        }

        $process = new \Symfony\Component\Process\Process(
            ['node', $script, $svgPath, $destPath, (string) $size],
            base_path(),
            null,
            null,
            60,
        );

        $process->run();

        return $process->isSuccessful() && is_file($destPath);
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
