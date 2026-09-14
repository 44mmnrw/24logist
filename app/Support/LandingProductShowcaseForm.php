<?php

namespace App\Support;

final class LandingProductShowcaseForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'product_showcase') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $data['product_showcase_banners'] = collect($extra['banners'] ?? [])
            ->filter(fn (mixed $banner): bool => is_array($banner))
            ->map(function (array $banner): array {
                $image = LandingMedia::normalizePath($banner['image'] ?? null);

                return [
                    'title' => (string) ($banner['title'] ?? ''),
                    'description' => (string) ($banner['description'] ?? ''),
                    'image' => $image ? [$image] : [],
                    'alt' => (string) ($banner['alt'] ?? ''),
                ];
            })
            ->values()
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function dehydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'product_showcase') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $banners = [];

        foreach ($data['product_showcase_banners'] ?? [] as $banner) {
            if (! is_array($banner)) {
                continue;
            }

            $title = trim((string) ($banner['title'] ?? ''));
            $description = trim((string) ($banner['description'] ?? ''));
            if ($title === '' && $description === '') {
                continue;
            }

            $banners[] = [
                'title' => $title,
                'description' => $description,
                'image' => LandingHeroCarouselForm::persistImage($banner['image'] ?? null, 'landing/product-showcase'),
                'alt' => trim((string) ($banner['alt'] ?? '')),
            ];
        }

        $extra['banners'] = $banners;
        unset($data['product_showcase_banners']);
        $data['extra'] = $extra;

        return $data;
    }
}
