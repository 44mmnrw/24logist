<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class LandingHeroCarouselForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'hero') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $slides = $extra['carousel_slides'] ?? [];

        if ($slides === [] && filled($data['dashboard_image'] ?? null)) {
            $slides = [
                [
                    'image' => $data['dashboard_image'],
                    'alt' => (string) ($extra['dashboard_image_alt'] ?? ''),
                ],
            ];
        }

        $data['hero_carousel_slides'] = collect($slides)
            ->map(function ($slide): array {
                if (! is_array($slide)) {
                    return ['image' => [], 'alt' => ''];
                }

                $path = LandingMedia::normalizePath($slide['image'] ?? null);

                return [
                    'image' => $path ? [$path] : [],
                    'alt' => (string) ($slide['alt'] ?? ''),
                ];
            })
            ->values()
            ->all();

        if (! isset($extra['carousel_delay_ms'])) {
            $extra['carousel_delay_ms'] = 5000;
        }

        $data['extra'] = $extra;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function dehydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'hero') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $slides = [];

        foreach ($data['hero_carousel_slides'] ?? [] as $slide) {
            if (! is_array($slide)) {
                continue;
            }

            $image = self::persistImage($slide['image'] ?? null);

            if ($image === null) {
                continue;
            }

            $slides[] = [
                'image' => $image,
                'alt' => trim((string) ($slide['alt'] ?? '')),
            ];
        }

        $extra['carousel_slides'] = $slides;
        unset($data['hero_carousel_slides']);
        $data['extra'] = $extra;

        return $data;
    }

    public static function persistImage(mixed $state): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state->store('landing/hero', 'public');
        }

        if (is_array($state)) {
            foreach ($state as $item) {
                $stored = self::persistImage($item);

                if ($stored !== null) {
                    return $stored;
                }
            }

            return null;
        }

        $path = LandingMedia::normalizePath($state);

        if ($path === null) {
            return null;
        }

        $publicDisk = Storage::disk('public');

        if ($publicDisk->exists($path)) {
            return $path;
        }

        if (Storage::exists($path)) {
            $filename = basename($path);
            $target = 'landing/hero/'.$filename;
            $publicDisk->put($target, Storage::get($path));

            return $target;
        }

        return null;
    }
}
