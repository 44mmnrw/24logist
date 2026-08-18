<?php

namespace App\Support;

use Illuminate\Support\Arr;

final class LandingGrowthForm
{
    /** @var array<string, string> */
    private const FIELD_MAP = [
        'growth_chart_title' => 'chart_title',
        'growth_chart_subtitle' => 'chart_subtitle',
        'growth_unit_percent_label' => 'unit_percent_label',
        'growth_unit_count_label' => 'unit_count_label',
        'growth_total_percent_value' => 'total_percent_value',
        'growth_total_percent_label' => 'total_percent_label',
        'growth_total_count_value' => 'total_count_value',
        'growth_total_count_label' => 'total_count_label',
        'growth_margin_segments' => 'margin_segments',
        'growth_customers_title' => 'customers_title',
        'growth_tab_count_label' => 'tab_count_label',
        'growth_tab_revenue_label' => 'tab_revenue_label',
        'growth_tab_margin_label' => 'tab_margin_label',
        'growth_customer_metrics' => 'customer_metrics',
        'growth_dashboard_aria_label' => 'dashboard_aria_label',
        'growth_unit_aria_label' => 'unit_aria_label',
        'growth_chart_aria_label' => 'chart_aria_label',
        'growth_tabs_aria_label' => 'tabs_aria_label',
    ];

    /** @param array<string, mixed> $data */
    public static function hydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'growth') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        foreach (self::FIELD_MAP as $field => $extraKey) {
            $data[$field] = $extra[$extraKey] ?? (in_array($extraKey, ['margin_segments', 'customer_metrics'], true) ? [] : '');
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    public static function dehydrate(array $data): array
    {
        if (($data['slug'] ?? null) !== 'growth') {
            return $data;
        }

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];

        foreach (self::FIELD_MAP as $field => $extraKey) {
            $value = $data[$field] ?? null;

            if (is_array($value) || trim((string) $value) !== '') {
                $extra[$extraKey] = $value;
            } else {
                unset($extra[$extraKey]);
            }
        }

        unset(
            $extra['paragraph_one'],
            $extra['paragraph_two'],
            $extra['paragraph_three'],
            $extra['lead_prefix'],
            $extra['lead_highlight'],
            $extra['lead_suffix'],
        );

        $data['extra'] = $extra;

        return Arr::except($data, array_keys(self::FIELD_MAP));
    }
}
