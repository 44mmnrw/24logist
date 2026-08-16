<?php

namespace App\Services;

use App\Models\BlogTag;
use GdImage;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BlogTagSocialImageGenerator
{
    private const WIDTH = 1200;

    private const HEIGHT = 630;

    private const LAYOUT_VERSION = '2';

    private const LOGO_WIDTH = 170;

    private const LOGO_X = 82;

    private const LOGO_Y = 108;

    public function generate(BlogTag $tag): string
    {
        $this->assertGdIsAvailable();

        $canvas = $this->createCanvas();

        try {
            $this->placeLogo($canvas);
            $this->placeTitle($canvas, $tag->socialImageTitle());

            $imageHash = substr(hash('sha256', self::LAYOUT_VERSION.'|'.$tag->socialImageTitle()), 0, 12);
            $path = 'blog/tags/generated/'.$tag->slug.'-'.$imageHash.'.webp';
            $disk = Storage::disk('public');
            $disk->makeDirectory(dirname($path));

            if (! imagewebp($canvas, $disk->path($path), 90)) {
                throw new RuntimeException('Не удалось сохранить изображение тега.');
            }

            collect([$tag->og_image_path, $tag->twitter_image_path, $tag->schema_image_path])
                ->filter(fn (?string $oldPath): bool => filled($oldPath)
                    && $oldPath !== $path
                    && str_starts_with($oldPath, 'blog/tags/generated/'))
                ->unique()
                ->each(fn (string $oldPath) => $disk->delete($oldPath));

            $tag->forceFill([
                'og_image_path' => $path,
                'twitter_image_path' => $path,
                'schema_image_path' => $path,
            ])->saveQuietly();

            return $path;
        } finally {
            imagedestroy($canvas);
        }
    }

    private function assertGdIsAvailable(): void
    {
        foreach (['imagecreatefrompng', 'imagettftext', 'imagewebp'] as $function) {
            if (! function_exists($function)) {
                throw new RuntimeException('Для генерации изображений необходимо PHP-расширение GD с FreeType и WebP.');
            }
        }
    }

    private function createCanvas(): GdImage
    {
        $sourcePath = resource_path('images/blog-tag-og-base.png');
        $source = @imagecreatefrompng($sourcePath);

        if (! $source) {
            throw new RuntimeException('Не найден фон для изображений тегов: '.$sourcePath);
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);

        if (! $canvas) {
            imagedestroy($source);
            throw new RuntimeException('Не удалось создать холст изображения.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max(self::WIDTH / $sourceWidth, self::HEIGHT / $sourceHeight);
        $scaledWidth = (int) round($sourceWidth * $scale);
        $scaledHeight = (int) round($sourceHeight * $scale);
        $destinationX = (int) round((self::WIDTH - $scaledWidth) / 2);
        $destinationY = (int) round((self::HEIGHT - $scaledHeight) / 2);

        imagecopyresampled(
            $canvas,
            $source,
            $destinationX,
            $destinationY,
            0,
            0,
            $scaledWidth,
            $scaledHeight,
            $sourceWidth,
            $sourceHeight,
        );

        imagedestroy($source);

        return $canvas;
    }

    private function placeLogo(GdImage $canvas): void
    {
        $logoPath = resource_path('images/blog-tag-logo.png');
        $logo = @imagecreatefrompng($logoPath);

        if (! $logo) {
            throw new RuntimeException('Не найден логотип для изображений тегов: '.$logoPath);
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);
        $logoWidth = imagesx($logo);
        $logoHeight = imagesy($logo);
        $renderedHeight = (int) round($logoHeight * (self::LOGO_WIDTH / $logoWidth));

        imagecopyresampled(
            $canvas,
            $logo,
            self::LOGO_X,
            self::LOGO_Y,
            0,
            0,
            self::LOGO_WIDTH,
            $renderedHeight,
            $logoWidth,
            $logoHeight,
        );
        imagedestroy($logo);
    }

    private function placeTitle(GdImage $canvas, string $title): void
    {
        $font = resource_path('fonts/NotoSans-VariableFont_wdth,wght.ttf');

        if (! is_file($font)) {
            throw new RuntimeException('Не найден шрифт для изображений тегов: '.$font);
        }

        $maximumWidth = 530;
        $fontSize = 54;
        $lines = [];

        while ($fontSize >= 34) {
            $lines = $this->wrapText($title, $font, $fontSize, $maximumWidth);

            if (count($lines) <= 3) {
                break;
            }

            $fontSize -= 2;
        }

        if (count($lines) > 3) {
            $lines = array_slice($lines, 0, 3);
            $lines[2] = rtrim($lines[2], '.,;:!?—-').'…';
        }

        $color = imagecolorallocate($canvas, 11, 31, 66);
        $lineHeight = (int) round($fontSize * 1.22);
        $blockHeight = count($lines) * $lineHeight;
        $baseline = max(255, (int) round((self::HEIGHT - $blockHeight) / 2) + $fontSize);

        foreach ($lines as $line) {
            imagettftext($canvas, $fontSize, 0, 72, $baseline, $color, $font, $line);
            $baseline += $lineHeight;
        }
    }

    /** @return array<int, string> */
    private function wrapText(string $text, string $font, int $fontSize, int $maximumWidth): array
    {
        $words = preg_split('/\s+/u', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;

            if ($this->textWidth($candidate, $font, $fontSize) <= $maximumWidth) {
                $line = $candidate;

                continue;
            }

            if ($line !== '') {
                $lines[] = $line;
            }

            $line = $word;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: ['24Logist'];
    }

    private function textWidth(string $text, string $font, int $fontSize): int
    {
        $box = imagettfbbox($fontSize, 0, $font, $text);

        return $box ? abs($box[2] - $box[0]) : 0;
    }
}
