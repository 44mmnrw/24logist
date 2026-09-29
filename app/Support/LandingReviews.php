<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class LandingReviews
{
    /** @var array<string, string> */
    public const CLIENT_TYPES = [
        'legal_entity' => 'Юрлицо',
        'individual_entrepreneur' => 'ИП',
    ];

    /** @var array<string, string> */
    public const SEGMENTS = [
        'forwarder' => 'Экспедитор',
        'carrier' => 'Перевозчик',
        'cargo_owner' => 'Грузовладелец',
        'other' => 'Другое',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $existingExtra
     * @return array<string, mixed>
     */
    public static function prepareForSave(array $data, array $existingExtra = []): array
    {
        $incomingExtra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $extra = array_merge($existingExtra, $incomingExtra);
        if (array_key_exists('review_metrics', $data)) {
            $extra['metrics'] = is_array($data['review_metrics']) ? $data['review_metrics'] : [];
            unset($data['review_metrics']);
        }
        $approved = filter_var($extra['publication_approved'] ?? false, FILTER_VALIDATE_BOOL);

        if (($data['is_active'] ?? false) && ! $approved) {
            throw ValidationException::withMessages([
                'extra.publication_approved' => 'Подтвердите согласование отзыва с клиентом перед публикацией.',
            ]);
        }

        $metrics = collect($extra['metrics'] ?? [])
            ->filter(fn ($metric): bool => is_array($metric))
            ->map(fn (array $metric): array => [
                'value' => trim((string) ($metric['value'] ?? '')),
                'label' => trim((string) ($metric['label'] ?? '')),
            ])
            ->filter(fn (array $metric): bool => $metric['value'] !== '' && $metric['label'] !== '')
            ->take(2)
            ->values()
            ->all();

        $wasApproved = filter_var($existingExtra['publication_approved'] ?? false, FILTER_VALIDATE_BOOL);
        $extra['publication_approved'] = $approved;
        $extra['metrics'] = $metrics;
        $extra['logo_path'] = LandingMedia::normalizePath($extra['logo_path'] ?? null);

        foreach (['region', 'representative_name', 'representative_position'] as $key) {
            $value = trim((string) ($extra[$key] ?? ''));
            $extra[$key] = $value !== '' ? $value : null;
        }

        if ($approved && ! $wasApproved) {
            $extra['publication_approved_at'] = now()->toIso8601String();
            $extra['publication_approved_by'] = auth()->id();
        } elseif (! $approved) {
            $extra['publication_approved_at'] = null;
            $extra['publication_approved_by'] = null;
        }

        $data['block_type'] = 'review';
        $data['extra'] = $extra;

        return $data;
    }

    public static function isApproved(LandingBlock $review): bool
    {
        $extra = is_array($review->extra) ? $review->extra : [];

        return $review->block_type === 'review'
            && filter_var($extra['publication_approved'] ?? false, FILTER_VALIDATE_BOOL);
    }

    /** @param  Collection<int, LandingBlock>  $blocks */
    public static function renderable(Collection $blocks): Collection
    {
        return $blocks
            ->filter(fn (LandingBlock $review): bool => $review->is_active && self::isApproved($review))
            ->sortBy('sort_order')
            ->values();
    }

    /** @return array<int, array{value: string, label: string}> */
    public static function metrics(LandingBlock $review): array
    {
        $extra = is_array($review->extra) ? $review->extra : [];

        return collect($extra['metrics'] ?? [])
            ->filter(fn ($metric): bool => is_array($metric))
            ->map(fn (array $metric): array => [
                'value' => trim((string) ($metric['value'] ?? '')),
                'label' => trim((string) ($metric['label'] ?? '')),
            ])
            ->filter(fn (array $metric): bool => $metric['value'] !== '' && $metric['label'] !== '')
            ->take(2)
            ->values()
            ->all();
    }

    public static function clientTypeLabel(LandingBlock $review): ?string
    {
        $key = (string) (($review->extra ?? [])['client_type'] ?? '');

        return self::CLIENT_TYPES[$key] ?? null;
    }

    public static function segmentLabel(LandingBlock $review): ?string
    {
        $key = (string) (($review->extra ?? [])['segment'] ?? '');

        return self::SEGMENTS[$key] ?? null;
    }

    public static function monogram(string $company): string
    {
        $company = preg_replace('/^(?:ООО|АО|ПАО|ИП)\s*[«"“]?/ui', '', trim($company)) ?? $company;
        $company = trim($company, " \t\n\r\0\x0B«»\"“”");
        $words = preg_split('/[^\p{L}\p{N}]+/u', $company, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return 'ЛР';
        }

        if (count($words) > 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($words[0], 0, 2));
    }
}
