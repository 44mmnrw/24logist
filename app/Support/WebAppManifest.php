<?php

namespace App\Support;

final class WebAppManifest
{
    /**
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        $siteUrl = rtrim((string) config('app.url'), '/');

        return [
            'name' => OpenGraph::SITE_NAME,
            'short_name' => OpenGraph::SITE_NAME,
            'start_url' => $siteUrl.'/',
            'scope' => $siteUrl.'/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#1d4ed8',
            'lang' => 'ru',
            'icons' => collect(PwaIcons::SIZES)
                ->map(fn (int $size): array => [
                    'src' => PwaIcons::url($size),
                    'sizes' => $size.'x'.$size,
                    'type' => 'image/png',
                    'purpose' => 'any',
                ])
                ->all(),
        ];
    }

    public static function url(): string
    {
        return rtrim((string) config('app.url'), '/').'/site.webmanifest';
    }
}
