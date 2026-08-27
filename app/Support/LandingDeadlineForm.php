<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class LandingDeadlineForm
{
    /** @var array<string, string> */
    private const TEXT_FIELDS = [
        'deadline_kicker' => 'deadline_kicker',
        'deadline_date' => 'deadline_date',
        'deadline_text' => 'deadline_text',
        'deadline_button_text' => 'deadline_button_text',
    ];

    /** @param array<string, mixed> $data */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'platform') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        foreach (self::TEXT_FIELDS as $field => $extraKey) {
            $data[$field] = (string) ($extra[$extraKey] ?? '');
        }

        $data['deadline_icon'] = LandingIcons::resolve($extra['deadline_icon'] ?? null);

        return $data;
    }

    /** @param array<string, mixed> $data */
    public static function dehydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'platform') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        foreach (self::TEXT_FIELDS as $field => $extraKey) {
            $value = trim((string) ($data[$field] ?? ''));

            if ($value !== '') {
                $extra[$extraKey] = $value;
            } else {
                unset($extra[$extraKey]);
            }
        }

        $icon = LandingIcons::normalize($data['deadline_icon'] ?? null);

        if ($icon !== null) {
            $extra['deadline_icon'] = $icon;
        } else {
            unset($extra['deadline_icon']);
        }

        $data['extra'] = $extra;

        return Arr::except($data, [
            ...array_keys(self::TEXT_FIELDS),
            'deadline_icon',
        ]);
    }
}
