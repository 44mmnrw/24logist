<?php

namespace App\Support;

use App\Models\LandingSection;

final class LandingHeroCarousel
{
    /**
     * @return list<array{path: string, url: string, alt: string}>
     */
    public static function slides(?LandingSection $section): array
    {
        if ($section === null) {
            return [];
        }

        $extra = $section->extra ?? [];
        $defaultAlt = (string) ($extra['dashboard_image_alt'] ?? 'Интерфейс ЛогистРу');
        $slides = [];

        foreach ($extra['carousel_slides'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $path = LandingMedia::normalizePath($item['image'] ?? null);
            $url = LandingMedia::url($path);

            if ($path === null || $url === null) {
                continue;
            }

            $alt = trim((string) ($item['alt'] ?? ''));

            $slides[] = [
                'path' => $path,
                'url' => $url,
                'alt' => $alt !== '' ? $alt : $defaultAlt,
            ];
        }

        if ($slides !== []) {
            return $slides;
        }

        $legacyUrl = LandingMedia::url($section->dashboard_image);

        if ($legacyUrl === null) {
            return [];
        }

        return [
            [
                'path' => $section->dashboard_image,
                'url' => $legacyUrl,
                'alt' => $defaultAlt,
            ],
        ];
    }

    public static function delayMs(?LandingSection $section): int
    {
        $ms = (int) ($section?->extra['carousel_delay_ms'] ?? 5000);

        return max(2000, min(60_000, $ms > 0 ? $ms : 5000));
    }
}
