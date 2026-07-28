<?php

namespace App\Services;

use App\Support\ImageVariants;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

final class ImageVariantGenerator
{
    /**
     * @param  list<int>  $widths
     * @return array{status: 'generated'|'skipped'|'failed', generated: list<string>, skipped: list<string>, message: ?string}
     */
    public function generate(string $path, array $widths, bool $force = false): array
    {
        if (! config('image-optimizer.enabled', true)) {
            return $this->result('skipped', message: 'Image optimizer is disabled.');
        }

        $path = ltrim(trim($path), '/');
        $disk = Storage::disk('public');

        if (! ImageVariants::isOptimizableOriginal($path) || ! $disk->exists($path)) {
            return $this->result('skipped', message: 'Source is missing or unsupported.');
        }

        $script = (string) config('image-optimizer.script');

        if ($script === '' || ! is_file($script)) {
            return $this->failure($path, 'Image optimizer script is missing.');
        }

        $widths = collect($widths)
            ->map(fn (mixed $width): int => (int) $width)
            ->filter(fn (int $width): bool => $width > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($widths === []) {
            return $this->result('skipped', message: 'No target widths configured.');
        }

        $process = new Process(
            [
                (string) config('image-optimizer.node_binary', 'node'),
                $script,
                $disk->path($path),
                implode(',', $widths),
                (string) config('image-optimizer.webp_quality', 82),
                (string) config('image-optimizer.avif_quality', 62),
                $force ? '1' : '0',
            ],
            base_path(),
            null,
            null,
            max(10, (int) config('image-optimizer.timeout', 120)),
        );

        try {
            $process->run();
        } catch (Throwable $exception) {
            return $this->failure($path, $exception->getMessage());
        }

        if (! $process->isSuccessful()) {
            return $this->failure($path, trim($process->getErrorOutput()) ?: 'Sharp process failed.');
        }

        try {
            $payload = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            return $this->failure($path, 'Invalid optimizer response: '.$exception->getMessage());
        }

        $generated = $this->relativePaths($payload['generated'] ?? [], $disk->path(''));
        $skipped = $this->relativePaths($payload['skipped'] ?? [], $disk->path(''));

        return $this->result(
            $generated !== [] ? 'generated' : 'skipped',
            $generated,
            $skipped,
        );
    }

    /**
     * @param  list<string>  $generated
     * @param  list<string>  $skipped
     * @return array{status: 'generated'|'skipped'|'failed', generated: list<string>, skipped: list<string>, message: ?string}
     */
    private function result(
        string $status,
        array $generated = [],
        array $skipped = [],
        ?string $message = null,
    ): array {
        return compact('status', 'generated', 'skipped', 'message');
    }

    /**
     * @return array{status: 'failed', generated: list<string>, skipped: list<string>, message: string}
     */
    private function failure(string $path, string $message): array
    {
        Log::warning('Image variant generation failed.', [
            'path' => $path,
            'message' => $message,
        ]);

        return $this->result('failed', message: $message);
    }

    /**
     * @return list<string>
     */
    private function relativePaths(mixed $paths, string $root): array
    {
        if (! is_array($paths)) {
            return [];
        }

        $root = rtrim(str_replace('\\', '/', $root), '/').'/';

        return collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && $path !== '')
            ->map(function (string $path) use ($root): string {
                $path = str_replace('\\', '/', $path);

                return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
            })
            ->values()
            ->all();
    }
}
