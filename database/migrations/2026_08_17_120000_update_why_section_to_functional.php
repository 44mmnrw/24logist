<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('landing_sections')->updateOrInsert(
            ['slug' => 'why'],
            [
                'name' => 'Функционал',
                'title' => 'Функционал',
                'subtitle' => 'Всё, что нужно экспедитору для работы с заявкой — от оформления и документооборота до аналитики прибыли и проверки контрагентов.',
                'extra' => json_encode([
                    'quote' => 'Наша главная ценность в том, что мы упрощаем процесс работы с заявкой от Заказчика, делаем его простым и удобным',
                    'quote_initials' => 'СА',
                    'quote_author' => 'Станислав Аристов',
                    'quote_handle' => '@sss_air',
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'is_active' => true,
                'sort_order' => 3,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        DB::table('landing_blocks')->where('section_slug', 'why')->delete();

        foreach ($this->functionalCards() as $index => $card) {
            DB::table('landing_blocks')->insert([
                'section_slug' => 'why',
                'block_type' => 'card',
                'title' => $card['title'],
                'description' => $card['description'],
                'tag' => $card['tag'],
                'is_active' => true,
                'is_highlighted' => false,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->clearLandingCache();
    }

    public function down(): void
    {
        $now = now();

        DB::table('landing_sections')->where('slug', 'why')->update([
            'name' => 'Преимущества',
            'title' => 'Преимущества',
            'subtitle' => 'Делаем работу логиста предсказуемой: меньше звонков, чатов и потерянных задач — больше прозрачности.',
            'extra' => null,
            'updated_at' => $now,
        ]);

        DB::table('landing_blocks')->where('section_slug', 'why')->delete();

        foreach ([
            ['icon' => 'icon:document-fast', 'title' => 'Договор-заявка за 5 минут', 'description' => 'Автозаполнение реквизитов и шаблоны — оформление в несколько кликов.'],
            ['icon' => 'icon:chart-bar', 'title' => 'Аналитика и отчёты', 'description' => 'Следите за прибылью и эффективностью работы с каждым заказчиком.'],
            ['icon' => 'icon:lifebuoy', 'title' => 'Тех. поддержка', 'description' => 'Помогаем настроить процессы и оперативно отвечаем на вопросы.'],
            ['icon' => 'icon:shield-check', 'title' => 'Безопасность данных', 'description' => 'Серверы в РФ и соответствие требованиям ФЗ №140 от 07.06.2025 г.'],
            ['icon' => 'icon:document-signed', 'title' => 'Встроенный ЭДО', 'description' => 'Электронные перевозочные документы внутри сервиса — без дополнительных оплат.'],
        ] as $index => $card) {
            DB::table('landing_blocks')->insert([
                'section_slug' => 'why',
                'block_type' => 'card',
                'icon' => $card['icon'],
                'title' => $card['title'],
                'description' => $card['description'],
                'is_active' => true,
                'is_highlighted' => false,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->clearLandingCache();
    }

    /** @return array<int, array{title: string, description: string, tag: string}> */
    private function functionalCards(): array
    {
        return [
            ['title' => 'Заявка', 'description' => 'Первый договор-заявка оформляется за считанные минуты. На его основе автоматически заполняются доверенность, транспортная накладная и ЭТрН. Документы отправляются перевозчику через ЭДО или почту.', 'tag' => '3–5 минут на заявку'],
            ['title' => 'Документооборот и банковские выписки', 'description' => 'После доставки заявка попадает в документооборот. Пользователь указывает плановые даты оплат, а сервис напоминает о сроках. Загруженные выписки показывают все платежи в личном кабинете.', 'tag' => 'Контроль оплат'],
            ['title' => 'Зарплата и дополнительные расходы', 'description' => 'Администратор видит расходы на оклады и налоги, а менеджер — только свои показатели. Система автоматически считывает платежи и распределяет их по статьям доходов и расходов.', 'tag' => 'Учёт по ролям'],
            ['title' => 'ЭПД', 'description' => 'Обмен электронными перевозочными документами происходит в несколько кликов. Из входящих поручений экспедитору формируются заказ-заявки и ЭТрН.', 'tag' => 'Обмен в 1 клик'],
            ['title' => 'Прибыль', 'description' => 'Гибкая настройка аналитики и финансов, учёт дополнительных расходов, расчёт прибыли и маржинальности каждой заявки.', 'tag' => 'Маржа по заявке'],
            ['title' => 'Безопасность', 'description' => 'Рейтинг перевозчика в АТИ и проверка контрагентов доступны прямо в личном кабинете, без подключения дополнительных сервисов.', 'tag' => 'Проверка контрагентов'],
        ];
    }

    private function clearLandingCache(): void
    {
        Cache::forget('landing.page.content.v3');
        Cache::forget('landing.page.content');
    }
};
