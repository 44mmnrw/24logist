<?php

namespace App\Support;

final class BlogBodyImages
{
    /**
     * @return list<string>
     */
    public static function paths(?string $content): array
    {
        if (blank($content)) {
            return [];
        }

        preg_match_all('/<img\b[^>]*>/i', $content, $matches);

        return collect($matches[0] ?? [])
            ->map(static::pathFromImageTag(...))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function renderResponsive(string $html): string
    {
        return preg_replace_callback('/<img\b[^>]*>/i', function (array $matches): string {
            $imageTag = $matches[0];
            $path = self::pathFromImageTag($imageTag);

            if ($path === null) {
                return $imageTag;
            }

            $image = ImageVariants::data($path);

            if (! $image['avif_srcset'] && ! $image['webp_srcset']) {
                return $imageTag;
            }

            $sizes = '(max-width: 860px) calc(100vw - 32px), 760px';
            $sources = '';

            if ($image['avif_srcset']) {
                $sources .= '<source type="image/avif" srcset="'.self::escape($image['avif_srcset']).'" sizes="'.$sizes.'">';
            }

            if ($image['webp_srcset']) {
                $sources .= '<source type="image/webp" srcset="'.self::escape($image['webp_srcset']).'" sizes="'.$sizes.'">';
            }

            return '<picture class="blog-post-body__picture">'.$sources.$imageTag.'</picture>';
        }, $html) ?? $html;
    }

    private static function pathFromImageTag(string $imageTag): ?string
    {
        $path = self::attribute($imageTag, 'data-id');

        if ($path === null) {
            $source = self::attribute($imageTag, 'src');

            if ($source === null) {
                return null;
            }

            $urlPath = parse_url($source, PHP_URL_PATH);

            if (! is_string($urlPath)) {
                return null;
            }

            $path = str_starts_with($urlPath, '/storage/')
                ? substr($urlPath, strlen('/storage/'))
                : ltrim($urlPath, '/');
        }

        $path = str_replace('\\', '/', rawurldecode(trim($path)));

        if (
            ! str_starts_with($path, 'blog/body/')
            || str_contains($path, '../')
            || ! ImageVariants::isOptimizableOriginal($path)
        ) {
            return null;
        }

        return $path;
    }

    private static function attribute(string $imageTag, string $name): ?string
    {
        if (! preg_match('/\s'.preg_quote($name, '/').'\s*=\s*(["\'])(.*?)\1/is', $imageTag, $matches)) {
            return null;
        }

        $value = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $value !== '' ? $value : null;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
