<?php

namespace App\Console\Commands;

use App\Support\PwaIcons;
use App\Support\SiteIconRasterizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GeneratePwaIconsCommand extends Command
{
    protected $signature = 'icons:generate-pwa';

    protected $description = 'Generate fallback PWA icons (192×192 and 512×512)';

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('GD extension is required.');

            return self::FAILURE;
        }

        $source = $this->resolveSourcePath();

        if ($source === null) {
            $this->error('No source image found. Run icons:generate-apple-touch first or upload PNG in admin.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(public_path('images'));

        foreach (PwaIcons::SIZES as $size) {
            $output = public_path('images/icon-'.$size.'.png');

            if (! SiteIconRasterizer::rasterizeToSquare($source, $output, $size)) {
                $this->error('Failed: '.$output);

                return self::FAILURE;
            }

            $this->info('PNG: '.$output);
        }

        return self::SUCCESS;
    }

    private function resolveSourcePath(): ?string
    {
        $apple = public_path('images/apple-touch-icon.png');

        if (is_file($apple)) {
            return $apple;
        }

        $ogHero = public_path('images/og-hero.png');

        return is_file($ogHero) ? $ogHero : null;
    }
}
