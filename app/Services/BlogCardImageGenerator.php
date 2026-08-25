<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Support\ImageVariants;
use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BlogCardImageGenerator
{
    private const WIDTH = 1200;

    private const HEIGHT = 675;

    private const BACKGROUND_WIDTH = 160;

    private const BACKGROUND_HEIGHT = 90;

    private const LAYOUT_VERSION = '3';

    private const LOGO_WIDTH = 360;

    private const LOGO_INSET = 36;

    public function generate(BlogPost $post, bool $showLogo = false): string
    {
        $this->assertGdIsAvailable();

        $coverPath = trim((string) ($post->card_source_image_path ?: $post->cover_image_path));

        if ($coverPath === '') {
            throw new RuntimeException('У статьи не задана обложка.');
        }

        $disk = Storage::disk('public');
        $sourcePath = $this->resolveSourcePath($coverPath, $disk->path(''));

        if ($sourcePath === null) {
            throw new RuntimeException('Файл обложки не найден: '.$coverPath);
        }

        $source = $this->loadImage($sourcePath);
        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if (! $canvas) {
            imagedestroy($source);
            throw new RuntimeException('Не удалось создать холст миниатюры.');
        }

        try {
            $this->renderBlurredBackground($canvas, $source);
            $this->renderContainedImage($canvas, $source);

            if ($showLogo) {
                $this->placeLogo($canvas, $post->logoPosition());
            }

            $hash = substr(hash('sha256', implode('|', [
                self::LAYOUT_VERSION,
                hash_file('sha256', $sourcePath),
                $showLogo ? 'logo' : 'no-logo',
                $post->logoPosition(),
            ])), 0, 12);
            $path = 'blog/cards/generated/'.$post->slug.'-'.$hash.'.webp';
            $disk->makeDirectory(dirname($path));

            if (! imagewebp($canvas, $disk->path($path), 90)) {
                throw new RuntimeException('Не удалось сохранить миниатюру статьи.');
            }

            $oldPath = trim((string) $post->card_image_path);

            if ($oldPath !== '' && $oldPath !== $path && str_starts_with($oldPath, 'blog/cards/generated/')) {
                $disk->delete([
                    $oldPath,
                    ...array_values(ImageVariants::for($oldPath, 'avif')),
                    ...array_values(ImageVariants::for($oldPath, 'webp')),
                ]);
            }

            $post->forceFill([
                'card_image_path' => $path,
                'show_card_logo' => $showLogo,
            ])->saveQuietly();

            return $path;
        } finally {
            imagedestroy($source);
            imagedestroy($canvas);
        }
    }

    private function assertGdIsAvailable(): void
    {
        foreach (['imagecreatetruecolor', 'imagecreatefromstring', 'imagecreatefrompng', 'imagefilter', 'imagewebp'] as $function) {
            if (! function_exists($function)) {
                throw new RuntimeException('Для генерации миниатюр необходимо PHP-расширение GD с WebP.');
            }
        }
    }

    private function resolveSourcePath(string $path, string $storageRoot): ?string
    {
        if (str_starts_with($path, 'images/')) {
            $publicImages = realpath(public_path('images'));
            $source = realpath(public_path($path));

            if ($publicImages === false || $source === false) {
                return null;
            }

            $publicImages = rtrim(str_replace('\\', '/', $publicImages), '/').'/';
            $normalizedSource = str_replace('\\', '/', $source);

            return str_starts_with($normalizedSource, $publicImages) && is_file($source)
                ? $source
                : null;
        }

        $storageRoot = rtrim(str_replace('\\', '/', $storageRoot), '/').'/';
        $source = realpath($storageRoot.ltrim($path, '/'));

        if ($source === false) {
            return null;
        }

        $normalizedSource = str_replace('\\', '/', $source);

        return str_starts_with($normalizedSource, $storageRoot) && is_file($source)
            ? $source
            : null;
    }

    private function placeLogo(GdImage $canvas, string $position): void
    {
        $logoPath = public_path('images/logo/logo_platform.png');
        $logo = @imagecreatefrompng($logoPath);

        if (! $logo) {
            throw new RuntimeException('Не найден логотип для карточки статьи: '.$logoPath);
        }

        try {
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);
            $renderedHeight = (int) round($logoHeight * (self::LOGO_WIDTH / $logoWidth));
            $x = str_ends_with($position, 'right')
                ? self::WIDTH - self::LOGO_INSET - self::LOGO_WIDTH
                : self::LOGO_INSET;
            $y = str_starts_with($position, 'bottom')
                ? self::HEIGHT - self::LOGO_INSET - $renderedHeight
                : self::LOGO_INSET;

            imagealphablending($canvas, true);
            imagecopyresampled(
                $canvas,
                $logo,
                $x,
                $y,
                0,
                0,
                self::LOGO_WIDTH,
                $renderedHeight,
                $logoWidth,
                $logoHeight,
            );
        } finally {
            imagedestroy($logo);
        }
    }

    private function loadImage(string $path): GdImage
    {
        $contents = file_get_contents($path);
        $image = is_string($contents) ? @imagecreatefromstring($contents) : false;

        if (! $image) {
            throw new RuntimeException('Не удалось прочитать изображение обложки.');
        }

        return $image;
    }

    private function renderBlurredBackground(GdImage $canvas, GdImage $source): void
    {
        $background = imagecreatetruecolor(self::BACKGROUND_WIDTH, self::BACKGROUND_HEIGHT);

        if (! $background) {
            throw new RuntimeException('Не удалось подготовить фон миниатюры.');
        }

        try {
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $scale = max(self::BACKGROUND_WIDTH / $sourceWidth, self::BACKGROUND_HEIGHT / $sourceHeight);
            $width = (int) ceil($sourceWidth * $scale);
            $height = (int) ceil($sourceHeight * $scale);
            $x = (int) floor((self::BACKGROUND_WIDTH - $width) / 2);
            $y = (int) floor((self::BACKGROUND_HEIGHT - $height) / 2);

            imagecopyresampled(
                $background,
                $source,
                $x,
                $y,
                0,
                0,
                $width,
                $height,
                $sourceWidth,
                $sourceHeight,
            );

            for ($iteration = 0; $iteration < 6; $iteration++) {
                imagefilter($background, IMG_FILTER_GAUSSIAN_BLUR);
            }

            imagecopyresampled(
                $canvas,
                $background,
                0,
                0,
                0,
                0,
                self::WIDTH,
                self::HEIGHT,
                self::BACKGROUND_WIDTH,
                self::BACKGROUND_HEIGHT,
            );

            imagealphablending($canvas, true);
            $veil = imagecolorallocatealpha($canvas, 255, 255, 255, 82);
            imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $veil);
        } finally {
            imagedestroy($background);
        }
    }

    private function renderContainedImage(GdImage $canvas, GdImage $source): void
    {
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(self::WIDTH / $sourceWidth, self::HEIGHT / $sourceHeight);
        $width = (int) round($sourceWidth * $scale);
        $height = (int) round($sourceHeight * $scale);
        $x = (int) round((self::WIDTH - $width) / 2);
        $y = (int) round((self::HEIGHT - $height) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $x,
            $y,
            0,
            0,
            $width,
            $height,
            $sourceWidth,
            $sourceHeight,
        );
    }
}
