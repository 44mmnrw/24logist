<?php

namespace App\Console\Commands;

use App\Support\AppleTouchIcon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateAppleTouchIconCommand extends Command
{
    protected $signature = 'icons:generate-apple-touch
                            {--output=public/images/apple-touch-icon.png : Fallback PNG path relative to project root}';

    protected $description = 'Generate fallback Apple Touch Icon PNG (180×180)';

    public function handle(): int
    {
        $outputPath = base_path($this->option('output'));
        File::ensureDirectoryExists(dirname($outputPath));

        if (extension_loaded('gd') && $this->generateFromOgHero($outputPath)) {
            $this->info('PNG: '.$outputPath);

            return self::SUCCESS;
        }

        $cached = AppleTouchIcon::ensureCached();

        if ($cached !== null && is_file($cached)) {
            copy($cached, $outputPath);
            $this->info('PNG: '.$outputPath);

            return self::SUCCESS;
        }

        $this->error('GD is required, or upload PNG favicon / apple touch icon in admin.');

        return self::FAILURE;
    }

    private function generateFromOgHero(string $outputPath): bool
    {
        $source = public_path('images/og-hero.png');

        if (! is_file($source)) {
            return false;
        }

        $image = @imagecreatefrompng($source);

        if (! $image instanceof \GdImage) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $cropSize = (int) min($width * 0.42, $height - 112);
        $srcX = $width - $cropSize - 64;
        $srcY = 56;

        $target = imagecreatetruecolor(AppleTouchIcon::SIZE, AppleTouchIcon::SIZE);

        if ($target === false) {
            imagedestroy($image);

            return false;
        }

        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled(
            $target,
            $image,
            0,
            0,
            max(0, $srcX),
            $srcY,
            AppleTouchIcon::SIZE,
            AppleTouchIcon::SIZE,
            $cropSize,
            $cropSize,
        );

        $saved = imagepng($target, $outputPath);

        imagedestroy($image);
        imagedestroy($target);

        return $saved;
    }
}
