<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class LandingFunctionalForm
{
    /** @var array<string, string> */
    private const FIELD_MAP = [
        'functional_quote' => 'quote',
        'functional_quote_initials' => 'quote_initials',
        'functional_quote_author' => 'quote_author',
        'functional_quote_handle' => 'quote_handle',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'why') {
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
        if (($data['slug'] ?? null) !== 'why') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $quote = trim((string) ($data['functional_quote'] ?? ''));

        if ($quote === '') {
            foreach (self::FIELD_MAP as $extraKey) {
                unset($extra[$extraKey]);
            }
        } else {
            $extra['quote'] = $quote;

            foreach (array_slice(self::FIELD_MAP, 1, null, true) as $field => $extraKey) {
                $value = trim((string) ($data[$field] ?? ''));

                if ($value !== '') {
                    $extra[$extraKey] = $value;
                } else {
                    unset($extra[$extraKey]);
                }
            }
        }

        $data['extra'] = $extra;

        return Arr::except($data, array_keys(self::FIELD_MAP));
    }
}
