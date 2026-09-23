<?php

namespace App\Support;

use App\Models\LandingBlock;
use Illuminate\Support\Arr;

final class LandingPricing
{
    /** @var list<string> */
    private const WIDE_FIELDS = [
        'wide_additional_title',
        'wide_users_label',
        'wide_users_min',
        'wide_users_max',
        'wide_users_default',
        'wide_currency_suffix',
        'wide_year_currency_suffix',
    ];

    /**
     * @param  array<int, array{title?: string|null, icon?: string|null}>  $features
     */
    public static function syncFeatures(LandingBlock $plan, array $features): void
    {
        $plan->children()
            ->where('block_type', 'feature')
            ->delete();

        foreach ($features as $index => $feature) {
            $title = trim((string) ($feature['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $iconKey = filled($feature['icon'] ?? null)
                ? (string) $feature['icon']
                : 'check';

            LandingBlock::query()->create([
                'section_slug' => $plan->section_slug,
                'block_type' => 'feature',
                'parent_id' => $plan->id,
                'title' => $title,
                'icon' => LandingIcons::normalize($iconKey),
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return array<int, array{title: string, icon: ?string}>
     */
    public static function featuresFormState(LandingBlock $plan): array
    {
        return $plan->children()
            ->where('block_type', 'feature')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LandingBlock $feature): array => [
                'title' => $feature->title ?? '',
                'icon' => LandingIcons::resolve($feature->icon),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{title?: string|null, price?: int|string|null}>  $options
     */
    public static function syncOptions(LandingBlock $plan, array $options): void
    {
        $plan->children()->where('block_type', 'paid_option')->delete();

        foreach ($options as $index => $option) {
            $title = trim((string) ($option['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            LandingBlock::query()->create([
                'section_slug' => $plan->section_slug,
                'block_type' => 'paid_option',
                'parent_id' => $plan->id,
                'title' => $title,
                'price' => max(0, (int) ($option['price'] ?? 0)),
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }

    /** @return array<int, array{title: string, price: int}> */
    public static function optionsFormState(LandingBlock $plan): array
    {
        return $plan->children()
            ->where('block_type', 'paid_option')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (LandingBlock $option): array => [
                'title' => $option->title ?? '',
                'price' => max(0, (int) $option->price),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public static function wideFormState(LandingBlock $plan): array
    {
        $extra = is_array($plan->extra) ? $plan->extra : [];
        $minimum = min(500, max(1, (int) ($extra['users_min'] ?? 1)));
        $maximum = min(500, max($minimum, (int) ($extra['users_max'] ?? 20)));

        return [
            'wide_additional_title' => (string) ($extra['additional_title'] ?? 'Дополнительные возможности'),
            'wide_users_label' => (string) ($extra['users_label'] ?? 'Количество пользователей'),
            'wide_users_min' => $minimum,
            'wide_users_max' => $maximum,
            'wide_users_default' => min($maximum, max($minimum, (int) ($extra['users_default'] ?? $minimum))),
            'wide_currency_suffix' => (string) ($extra['currency_suffix'] ?? '₽/мес'),
            'wide_year_currency_suffix' => (string) ($extra['year_currency_suffix'] ?? '₽/год'),
            'plan_options' => self::optionsFormState($plan),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $currentExtra
     * @return array<string, mixed>
     */
    public static function wideExtraFromForm(array $data, array $currentExtra = []): array
    {
        $minimum = min(500, max(1, (int) ($data['wide_users_min'] ?? 1)));
        $maximum = min(500, max($minimum, (int) ($data['wide_users_max'] ?? 20)));
        $default = min($maximum, max($minimum, (int) ($data['wide_users_default'] ?? $minimum)));

        return array_merge($currentExtra, [
            'additional_title' => trim((string) ($data['wide_additional_title'] ?? 'Дополнительные возможности')),
            'users_label' => trim((string) ($data['wide_users_label'] ?? 'Количество пользователей')),
            'users_min' => $minimum,
            'users_max' => $maximum,
            'users_default' => $default,
            'currency_suffix' => trim((string) ($data['wide_currency_suffix'] ?? '₽/мес')),
            'year_currency_suffix' => trim((string) ($data['wide_year_currency_suffix'] ?? '₽/год')),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function stripVirtualFields(array $data): array
    {
        return Arr::except($data, [...self::WIDE_FIELDS, 'plan_features', 'plan_options']);
    }
}
