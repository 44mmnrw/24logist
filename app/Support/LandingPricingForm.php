<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class LandingPricingForm
{
    /** @var list<string> */
    private const FIELD_MAP = [
        'pricing_footnote' => 'footnote',
        'pricing_footnote_link_text' => 'footnote_link_text',
        'pricing_footnote_link' => 'footnote_link',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'pricing') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        foreach (self::FIELD_MAP as $field => $extraKey) {
            $data[$field] = (string) ($extra[$extraKey] ?? '');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function dehydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'pricing') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        foreach (self::FIELD_MAP as $field => $extraKey) {
            $value = trim((string) ($data[$field] ?? ''));

            if ($value !== '') {
                $extra[$extraKey] = $value;
            } else {
                unset($extra[$extraKey]);
            }
        }

        $data['extra'] = $extra;

        return Arr::except($data, array_keys(self::FIELD_MAP));
    }
}
