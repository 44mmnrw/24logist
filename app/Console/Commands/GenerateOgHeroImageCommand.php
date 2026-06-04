<?php

namespace App\Console\Commands;

use App\Support\OpenGraphHeroCard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Symfony\Component\Process\Process;

class GenerateOgHeroImageCommand extends Command
{
    protected $signature = 'og:generate-hero-png
                            {--output=public/images/og-hero.png : Path to PNG relative to project root}
                            {--html=storage/app/og-hero-card.html : Path to save HTML snapshot}';

    protected $description = 'Generate Open Graph PNG (1200×630) from landing hero content';

    public function handle(): int
    {
        $outputPath = base_path($this->option('output'));
        $htmlPath = base_path($this->option('html'));

        File::ensureDirectoryExists(dirname($outputPath));
        File::ensureDirectoryExists(dirname($htmlPath));

        $html = View::make('seo.og-hero-card', [
            'card' => OpenGraphHeroCard::dataForPng(),
        ])->render();

        File::put($htmlPath, $html);
        $this->info('HTML: '.$htmlPath);

        $script = base_path('script_ai/generate-og-hero-png.mjs');

        if (! is_file($script)) {
            $this->error('Script not found: '.$script);

            return self::FAILURE;
        }

        $process = new Process([
            'node',
            $script,
            $htmlPath,
            $outputPath,
        ], base_path(), null, null, 120);

        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->newLine();
            $this->warn('Install once: npm install puppeteer --save-dev');
            $this->warn('Or open in browser: '.url('/__og/hero-card').' and save screenshot 1200×630.');

            return self::FAILURE;
        }

        $this->info('PNG: '.$outputPath);

        return self::SUCCESS;
    }
}
