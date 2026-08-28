<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Support\BlogBodyImages;
use App\Support\LandingMedia;

final class BlogImageOptimizer
{
    public function __construct(
        private readonly ImageVariantGenerator $generator,
    ) {}

    /**
     * @return list<array{status: 'generated'|'skipped'|'failed', generated: list<string>, skipped: list<string>, message: ?string}>
     */
    public function optimizePost(BlogPost $post, bool $force = false): array
    {
        $images = [
            [
                'path' => LandingMedia::normalizePath($post->cover_image_path),
                'widths' => $this->configuredWidths('blog_cover', [640, 1280]),
            ],
            [
                'path' => LandingMedia::normalizePath($post->card_image_path),
                'widths' => $this->configuredWidths('blog_card', [640, 1200]),
            ],
        ];

        foreach (BlogBodyImages::paths($post->body) as $path) {
            $images[] = [
                'path' => $path,
                'widths' => $this->configuredWidths('blog_body', [480, 960, 1440]),
            ];
        }

        return collect($images)
            ->filter(fn (array $image): bool => $image['path'] !== null)
            ->unique('path')
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
        if (str_starts_with($path, 'blog/cards/')) {
            return $this->configuredWidths('blog_card', [640, 1200]);
        }

        if (str_starts_with($path, 'blog/body/')) {
            return $this->configuredWidths('blog_body', [480, 960, 1440]);
        }

        return $this->configuredWidths('blog_cover', [640, 1280]);
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
