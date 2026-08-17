<?php

namespace Database\Seeders;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use Illuminate\Database\Seeder;

class FunctionalSectionSeeder extends Seeder
{
    public function run(): void
    {
        $section = LandingSection::query()->firstOrCreate(
            ['slug' => 'why'],
            [
                'name' => 'Функционал',
                'title' => 'Функционал',
                'subtitle' => 'Всё, что нужно экспедитору для работы с заявкой — от оформления и документооборота до аналитики прибыли и проверки контрагентов.',
                'is_active' => true,
                'sort_order' => 3,
            ],
        );

        $section->extra = array_replace($this->quoteDefaults(), $section->extra ?? []);
        $section->save();

        foreach ($this->cards() as $index => $data) {
            $card = LandingBlock::query()->firstOrNew([
                'section_slug' => 'why',
                'block_type' => 'card',
                'sort_order' => $index + 1,
            ]);

            if (! $card->exists) {
                $card->fill([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'tag' => $data['tag'],
                    'is_active' => true,
                    'is_highlighted' => false,
                ]);
            }

            $card->extra = array_replace(
                ['icon_asset' => $data['icon_asset']],
                $card->extra ?? [],
            );
            $card->save();
        }

        app(LandingPageService::class)->clearCache();
    }

    /** @return array<string, string> */
    private function quoteDefaults(): array
    {
        return [
            'quote' => 'Наша главная ценность в том, что мы упрощаем процесс работы с заявкой от Заказчика, делаем его простым и удобным',
            'quote_initials' => 'СА',
            'quote_author' => 'Станислав Аристов',
            'quote_handle' => '@sss_air',
        ];
    }

    /** @return array<int, array{title: string, description: string, tag: string, icon_asset: string}> */
    private function cards(): array
    {
        return [
            ['title' => 'Заявка', 'description' => 'Первый договор-заявка оформляется за считанные минуты. На его основе автоматически заполняются доверенность, транспортная накладная и ЭТрН. Документы отправляются перевозчику через ЭДО или почту.', 'tag' => '3–5 минут на заявку', 'icon_asset' => 'images/functional/request.svg'],
            ['title' => 'Документооборот и банковские выписки', 'description' => 'После доставки заявка попадает в документооборот. Пользователь указывает плановые даты оплат, а сервис напоминает о сроках. Загруженные выписки показывают все платежи в личном кабинете.', 'tag' => 'Контроль оплат', 'icon_asset' => 'images/functional/payments.svg'],
            ['title' => 'Зарплата и дополнительные расходы', 'description' => 'Администратор видит расходы на оклады и налоги, а менеджер — только свои показатели. Система автоматически считывает платежи и распределяет их по статьям доходов и расходов.', 'tag' => 'Учёт по ролям', 'icon_asset' => 'images/functional/expenses.svg'],
            ['title' => 'ЭПД', 'description' => 'Обмен электронными перевозочными документами происходит в несколько кликов. Из входящих поручений экспедитору формируются заказ-заявки и ЭТрН.', 'tag' => 'Обмен в 1 клик', 'icon_asset' => 'images/functional/epd.svg'],
            ['title' => 'Прибыль', 'description' => 'Гибкая настройка аналитики и финансов, учёт дополнительных расходов, расчёт прибыли и маржинальности каждой заявки.', 'tag' => 'Маржа по заявке', 'icon_asset' => 'images/functional/profit.svg'],
            ['title' => 'Безопасность', 'description' => 'Рейтинг перевозчика в АТИ и проверка контрагентов доступны прямо в личном кабинете, без подключения дополнительных сервисов.', 'tag' => 'Проверка контрагентов', 'icon_asset' => 'images/functional/security.svg'],
        ];
    }
}
