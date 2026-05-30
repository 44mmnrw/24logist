<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class LandingQuizForm
{
    /** @var list<string> */
    private const TEXT_FIELDS = [
        'quiz_finish_title' => 'finish_title',
        'quiz_finish_description' => 'finish_description',
        'quiz_recommendation_title' => 'recommendation_title',
        'quiz_recommendation_description' => 'recommendation_description',
        'quiz_success_title' => 'success_title',
        'quiz_success_description' => 'success_description',
        'quiz_submit_button_text' => 'submit_button_text',
        'quiz_privacy_prefix' => 'privacy_prefix',
        'quiz_privacy_link_text' => 'privacy_link_text',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'quiz') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        foreach (self::TEXT_FIELDS as $field => $extraKey) {
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
        if (($data['slug'] ?? null) !== 'quiz') {
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

        $data['extra'] = $extra;

        return Arr::except($data, array_keys(self::TEXT_FIELDS));
    }
}
