<?php

namespace App\Services;

use App\Models\LandingSection;
use App\Support\LandingMedia;

final class LandingImageOptimizer
{
    public function __construct(
        private readonly ImageVariantGenerator $generator,
    ) {}

    /**
     * @return list<array{status: 'generated'|'skipped'|'failed', generated: list<string>, skipped: list<string>, message: ?string}>
     */
    public function optimizeSection(LandingSection $section, bool $force = false): array
    {
        return collect($this->pathsForSection($section))
            ->map(fn (array $image): array => $this->generator->generate(
                $image['path'],
                $image['widths'],
                $force,
            ))
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function widthsForPath(string $path): array
    {
        if (
            str_starts_with($path, 'landing/mobile/')
            || str_starts_with($path, 'landing/driver-cabinet/')
        ) {
            return $this->configuredWidths('mobile', [320, 640]);
        }

        return $this->configuredWidths('hero', [640, 1280]);
    }

    /**
     * @return list<array{path: string, widths: list<int>}>
     */
    private function pathsForSection(LandingSection $section): array
    {
        if ($section->slug === 'hero') {
            $extra = is_array($section->extra) ? $section->extra : [];
            $paths = collect($extra['carousel_slides'] ?? [])
                ->filter(fn (mixed $slide): bool => is_array($slide))
                ->map(fn (array $slide): ?string => LandingMedia::normalizePath($slide['image'] ?? null))
                ->filter()
                ->values();

            if ($paths->isEmpty()) {
                $paths->push(LandingMedia::normalizePath($section->dashboard_image));
            }

            return $paths
                ->filter()
                ->unique()
                ->map(fn (string $path): array => [
                    'path' => $path,
                    'widths' => $this->configuredWidths('hero', [640, 1280]),
                ])
                ->values()
                ->all();
        }

        if (in_array($section->slug, ['mobile', 'driver_cabinet'], true)) {
            $path = LandingMedia::normalizePath($section->mobile_image);

            return $path === null ? [] : [[
                'path' => $path,
                'widths' => $this->configuredWidths('mobile', [320, 640]),
            ]];
        }

        return [];
    }

    /**
     * @param  list<int>  $fallback
     * @return list<int>
     */
    private function configuredWidths(string $key, array $fallback): array
    {
        $widths = config("image-optimizer.widths.{$key}", $fallback);

        return collect(is_array($widths) ? $widths : $fallback)
            ->map(fn (mixed $width): int => (int) $width)
            ->filter(fn (int $width): bool => $width > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
