<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $section = DB::table('landing_sections')->where('slug', 'growth')->first();

        if (! $section) {
            return;
        }

        $extra = json_decode($section->extra ?? '[]', true) ?: [];
        $savedNames = array_values(array_map(
            fn (array $item): string => (string) ($item['name'] ?? ''),
            $extra['customer_names'] ?? [],
        ));
        $defaultNames = [
            'ООО "ГК «ЛОГОС»"',
            'АО "УКЗ"',
            'ООО "БУГУЛЬМИНСКИЙ СЕЛЬСКОХОЗЯЙСТВЕННЫЙ РЫНОК"',
            'ООО "МЕТАЛЛИНВЕСТСПБ"',
            'ООО "КЛИМАТ-КОМПЛЕКС"',
        ];
        $names = array_replace($defaultNames, array_filter($savedNames));

        $extra += [
            'chart_title' => 'Сегменты маржинальности заявок',
            'chart_subtitle' => 'Распределение по диапазонам маржи',
            'unit_percent_label' => '%',
            'unit_count_label' => 'шт.',
            'total_percent_value' => '100%',
            'total_percent_label' => 'Доля',
            'total_count_value' => '59',
            'total_count_label' => 'Заявок',
            'margin_segments' => [
                ['label' => 'от 5% до 9%', 'percent_value' => '16.9%', 'count_value' => '10'],
                ['label' => 'от 10% до 15%', 'percent_value' => '16.9%', 'count_value' => '10'],
                ['label' => 'от 16%', 'percent_value' => '55.9%', 'count_value' => '33'],
                ['label' => 'Вне диапазонов', 'percent_value' => '10.2%', 'count_value' => '6'],
            ],
            'customers_title' => 'Топ заказчиков',
            'tab_count_label' => 'По количеству заявок',
            'tab_revenue_label' => 'По выручке',
            'tab_margin_label' => 'По маржинальности',
            'customer_metrics' => [
                ['name' => $names[0], 'count_value' => '9', 'count_width' => 100, 'revenue_value' => '1,8 млн ₽', 'revenue_width' => 100, 'margin_value' => '16,9%', 'margin_width' => 92],
                ['name' => $names[1], 'count_value' => '7', 'count_width' => 78, 'revenue_value' => '1,4 млн ₽', 'revenue_width' => 78, 'margin_value' => '13,2%', 'margin_width' => 72],
                ['name' => $names[2], 'count_value' => '4', 'count_width' => 44, 'revenue_value' => '640 тыс. ₽', 'revenue_width' => 36, 'margin_value' => '11,6%', 'margin_width' => 63],
                ['name' => $names[3], 'count_value' => '4', 'count_width' => 44, 'revenue_value' => '980 тыс. ₽', 'revenue_width' => 54, 'margin_value' => '15,7%', 'margin_width' => 85],
                ['name' => $names[4], 'count_value' => '4', 'count_width' => 44, 'revenue_value' => '760 тыс. ₽', 'revenue_width' => 42, 'margin_value' => '18,4%', 'margin_width' => 100],
            ],
            'dashboard_aria_label' => 'Примеры аналитических отчётов ЛогистРу',
            'unit_aria_label' => 'Единица измерения',
            'chart_aria_label' => 'Распределение маржинальности заявок',
            'tabs_aria_label' => 'Показатель рейтинга',
        ];

        unset($extra['customer_names']);

        DB::table('landing_sections')->where('slug', 'growth')->update([
            'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);

        $this->forgetCache();
    }

    public function down(): void
    {
        $section = DB::table('landing_sections')->where('slug', 'growth')->first();

        if (! $section) {
            return;
        }

        $extra = json_decode($section->extra ?? '[]', true) ?: [];
        $extra['customer_names'] = array_map(
            fn (array $item): array => ['name' => (string) ($item['name'] ?? '')],
            $extra['customer_metrics'] ?? [],
        );

        foreach ([
            'chart_title', 'chart_subtitle', 'unit_percent_label', 'unit_count_label',
            'total_percent_value', 'total_percent_label', 'total_count_value', 'total_count_label',
            'margin_segments', 'customers_title', 'tab_count_label', 'tab_revenue_label',
            'tab_margin_label', 'customer_metrics', 'dashboard_aria_label', 'unit_aria_label',
            'chart_aria_label', 'tabs_aria_label',
        ] as $key) {
            unset($extra[$key]);
        }

        DB::table('landing_sections')->where('slug', 'growth')->update([
            'extra' => json_encode($extra, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);

        $this->forgetCache();
    }

    private function forgetCache(): void
    {
        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }
};
