<?php

namespace Database\Seeders;

use App\Models\LandingSection;
use App\Services\LandingPageService;
use Illuminate\Database\Seeder;

class GrowthSectionSeeder extends Seeder
{
    public function run(): void
    {
        $section = LandingSection::query()->firstOrNew(['slug' => 'growth']);

        if (! $section->exists) {
            $section->fill([
                'name' => 'Рост и эффективность',
                'title' => 'Повышайте эффективность и растите вместе с нами',
                'is_active' => true,
                'sort_order' => 9,
            ]);
        }

        $extra = $section->extra ?? [];

        if (blank($section->description)) {
            $paragraphOne = trim(
                (string) ($extra['paragraph_one'] ?? '') ?: trim(
                (string) ($extra['lead_prefix'] ?? '').' '.
                (string) ($extra['lead_highlight'] ?? '').
                (string) ($extra['lead_suffix'] ?? ''),
                ),
            );

            $section->description = collect([
                $paragraphOne,
                trim((string) ($extra['paragraph_two'] ?? '')),
                trim((string) ($extra['paragraph_three'] ?? '')),
            ])->filter()->implode(PHP_EOL.PHP_EOL) ?: $this->defaultDescription();
        }

        foreach ($this->defaults() as $key => $defaultValue) {
            if (! array_key_exists($key, $extra) || blank($extra[$key])) {
                $extra[$key] = $defaultValue;
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
        $section->extra = $extra;
        $section->save();

        app(LandingPageService::class)->clearCache();
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
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
                ['name' => 'ООО "ГК «ЛОГОС»"', 'count_value' => '9', 'count_width' => 100, 'revenue_value' => '1,8 млн ₽', 'revenue_width' => 100, 'margin_value' => '16,9%', 'margin_width' => 92],
                ['name' => 'АО "УКЗ"', 'count_value' => '7', 'count_width' => 78, 'revenue_value' => '1,4 млн ₽', 'revenue_width' => 78, 'margin_value' => '13,2%', 'margin_width' => 72],
                ['name' => 'ООО "БУГУЛЬМИНСКИЙ СЕЛЬСКОХОЗЯЙСТВЕННЫЙ РЫНОК"', 'count_value' => '4', 'count_width' => 44, 'revenue_value' => '640 тыс. ₽', 'revenue_width' => 36, 'margin_value' => '11,6%', 'margin_width' => 63],
                ['name' => 'ООО "МЕТАЛЛИНВЕСТСПБ"', 'count_value' => '4', 'count_width' => 44, 'revenue_value' => '980 тыс. ₽', 'revenue_width' => 54, 'margin_value' => '15,7%', 'margin_width' => 85],
                ['name' => 'ООО "КЛИМАТ-КОМПЛЕКС"', 'count_value' => '4', 'count_width' => 44, 'revenue_value' => '760 тыс. ₽', 'revenue_width' => 42, 'margin_value' => '18,4%', 'margin_width' => 100],
            ],
            'dashboard_aria_label' => 'Примеры аналитических отчётов ЛогистРу',
            'unit_aria_label' => 'Единица измерения',
            'chart_aria_label' => 'Распределение маржинальности заявок',
            'tabs_aria_label' => 'Показатель рейтинга',
        ];
    }

    private function defaultDescription(): string
    {
        return implode(PHP_EOL.PHP_EOL, [
            'Работа в нашей системе освободит от 30 до 60% времени, которое вы раньше тратили на составление, редактирование и учёт транспортных документов в таблицах или сторонних сервисах.',
            'Это время вы сможете направить на поиск новых клиентов и работу с действующими заказчиками.',
            'Занимайтесь новыми проектами, не отвлекаясь от текущих задач, имея круглосуточный доступ к личному кабинету с любого из ваших устройств.',
        ]);
    }
}
