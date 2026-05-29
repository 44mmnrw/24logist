<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class LandingFooterForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'footer') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        $data['footer_copyright'] = (string) ($extra['copyright'] ?? '');
        $data['footer_tagline'] = (string) ($extra['tagline'] ?? '');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function dehydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'footer') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        $copyright = trim((string) ($data['footer_copyright'] ?? ''));
        $tagline = trim((string) ($data['footer_tagline'] ?? ''));

        if ($copyright !== '') {
            $extra['copyright'] = $copyright;
        } else {
            unset($extra['copyright']);
        }

        if ($tagline !== '') {
            $extra['tagline'] = $tagline;
        } else {
            unset($extra['tagline']);
        }

        $data['extra'] = $extra;

        return Arr::except($data, [
            'footer_copyright',
            'footer_tagline',
        ]);
    }
}
