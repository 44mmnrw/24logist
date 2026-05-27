<?php

namespace App\Support;

use App\Models\LandingSection;

final class LandingHeroCarousel
{
    /**
     * @return list<array{url: string, alt: string}>
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

            $url = LandingMedia::url($item['image'] ?? null);

            if ($url === null) {
                continue;
            }

            $alt = trim((string) ($item['alt'] ?? ''));

            $slides[] = [
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
