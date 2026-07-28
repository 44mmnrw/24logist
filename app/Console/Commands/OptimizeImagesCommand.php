<?php

namespace App\Console\Commands;

use App\Services\ImageVariantGenerator;
use App\Services\LandingImageOptimizer;
use App\Support\ImageVariants;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeImagesCommand extends Command
{
    protected $signature = 'images:optimize
                            {--directory=landing : Directory on the public disk}
                            {--force : Rebuild variants even when they are fresh}';

    protected $description = 'Generate responsive AVIF and WebP variants for public images';

    public function handle(
        ImageVariantGenerator $generator,
        LandingImageOptimizer $landingOptimizer,
    ): int {
        $directory = trim((string) $this->option('directory'), '/\\ ');

        if ($directory === '' || str_contains($directory, '..')) {
            $this->error('The directory must be a safe relative path on the public disk.');

            return self::FAILURE;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($directory)) {
            $this->error("Directory does not exist on the public disk: {$directory}");

            return self::FAILURE;
        }

        $files = collect($disk->allFiles($directory))
            ->filter(fn (string $path): bool => ImageVariants::isOptimizableOriginal($path))
            ->sort()
            ->values();

        if ($files->isEmpty()) {
            $this->warn("No optimizable images found in {$directory}.");

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $generated = 0;
        $skipped = 0;
        $failed = 0;

        $this->withProgressBar($files, function (string $path) use (
            $force,
            $generator,
            $landingOptimizer,
            &$generated,
            &$skipped,
            &$failed,
        ): void {
            $result = $generator->generate(
                $path,
                $landingOptimizer->widthsForPath($path),
                $force,
            );

            $generated += count($result['generated']);
            $skipped += count($result['skipped']);

            if ($result['status'] === 'failed') {
                $failed++;
            }
        });

        $this->newLine(2);
        $this->line("Sources: {$files->count()}");
        $this->line("Generated: {$generated}");
        $this->line("Fresh/skipped: {$skipped}");

        if ($failed > 0) {
            $this->error("Failed sources: {$failed}. See storage/logs/laravel.log.");

            return self::FAILURE;
        }

        $this->info('Image variants are ready.');

        return self::SUCCESS;
    }
}
