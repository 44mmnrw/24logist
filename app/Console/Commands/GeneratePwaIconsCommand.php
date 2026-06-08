<?php

namespace App\Console\Commands;

use App\Support\PwaIcons;
use App\Support\SiteIconRasterizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GeneratePwaIconsCommand extends Command
{
    protected $signature = 'icons:generate-pwa';

    protected $description = 'Generate PWA icons (192×192 and 512×512) from public/images/favicon.svg';

    public function handle(): int
    {
        File::ensureDirectoryExists(public_path('images'));

        foreach (PwaIcons::SIZES as $size) {
            $output = public_path('images/icon-'.$size.'.png');

            if (! SiteIconRasterizer::rasterizeBrandSvg($output, $size)) {
                $this->error('Failed: '.$output);
                $this->warn('Run once: npm install sharp --save-dev');

                return self::FAILURE;
            }

            $this->info('PNG: '.$output);
        }

        return self::SUCCESS;
    }
}
