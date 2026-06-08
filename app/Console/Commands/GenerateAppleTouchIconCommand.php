<?php

namespace App\Console\Commands;

use App\Support\AppleTouchIcon;
use App\Support\PwaIcons;
use App\Support\SiteIconRasterizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateAppleTouchIconCommand extends Command
{
    protected $signature = 'icons:generate-apple-touch
                            {--output=public/images/apple-touch-icon.png : Fallback PNG path relative to project root}';

    protected $description = 'Generate Apple Touch Icon PNG (180×180) from public/images/favicon.svg';

    public function handle(): int
    {
        $outputPath = base_path($this->option('output'));
        File::ensureDirectoryExists(dirname($outputPath));

        if (! SiteIconRasterizer::rasterizeBrandSvg($outputPath, AppleTouchIcon::SIZE)) {
            $this->error('Failed to rasterize '.SiteIconRasterizer::brandSvgPath());
            $this->warn('Run once: npm install sharp --save-dev');

            return self::FAILURE;
        }

        $this->info('PNG: '.$outputPath);

        return self::SUCCESS;
    }
}
