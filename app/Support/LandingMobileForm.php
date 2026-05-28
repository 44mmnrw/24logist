<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class LandingMobileForm
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'mobile') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        $data['extra'] = $extra;
        $data['mobile_pill_left_text'] = (string) ($extra['pill_left_text'] ?? '');
        $data['mobile_pill_left_icon'] = LandingIcons::resolve($extra['pill_left_icon'] ?? null);
        $data['mobile_pill_right_text'] = (string) ($extra['pill_right_text'] ?? '');
        $data['mobile_pill_right_icon'] = LandingIcons::resolve($extra['pill_right_icon'] ?? null);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function dehydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'mobile') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        $leftText = trim((string) ($data['mobile_pill_left_text'] ?? ''));
        $rightText = trim((string) ($data['mobile_pill_right_text'] ?? ''));

        if ($leftText !== '') {
            $extra['pill_left_text'] = $leftText;
            $extra['pill_left_icon'] = LandingIcons::normalize($data['mobile_pill_left_icon'] ?? null);
        } else {
            unset($extra['pill_left_text'], $extra['pill_left_icon']);
        }

        if ($rightText !== '') {
            $extra['pill_right_text'] = $rightText;
            $extra['pill_right_icon'] = LandingIcons::normalize($data['mobile_pill_right_icon'] ?? null);
        } else {
            unset($extra['pill_right_text'], $extra['pill_right_icon']);
        }

        $data['extra'] = $extra;

        return Arr::except($data, [
            'mobile_pill_left_text',
            'mobile_pill_left_icon',
            'mobile_pill_right_text',
            'mobile_pill_right_icon',
        ]);
    }
}
