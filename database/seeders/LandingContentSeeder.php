<?php

namespace Database\Seeders;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Support\LandingIcons;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    private function icon(string $name): string
    {
        return LandingIcons::toStorage($name);
    }

    public function run(): void
    {
        LandingBlock::query()->delete();
        LandingSection::query()->delete();

        $this->seedHeader();
        $this->seedHero();
        $this->seedWhy();
        $this->seedFunctional();
        $this->seedPlatform();
        $this->seedFeatures();
        $this->seedPricing();
        $this->seedAdditionalOptions();
        $this->seedEpdPlatform();
        $this->seedGrowth();
        $this->seedMobile();
        $this->seedDriverCabinet();
        $this->seedQuiz();
        $this->seedFaq();
        $this->seedFinalCta();
        $this->seedFooter();
    }

    private function section(array $data): LandingSection
    {
        return LandingSection::query()->create($data);
    }

    private function block(array $data): LandingBlock
    {
        return LandingBlock::query()->create($data);
    }

    private function seedHeader(): void
    {
        $section = $this->section([
            'slug' => 'header',
            'name' => 'Шапка',
            'sort_order' => 1,
            'extra' => [
                'logo_icon' => $this->icon('brand-logo'),
                'brand_name' => 'ЛогистРу',
            ],
        ]);

        foreach ([
            ['title' => 'Возможности', 'link' => '#features'],
            ['title' => 'Почему мы', 'link' => '#why'],
            ['title' => 'Тарифы', 'link' => '#pricing'],
            ['title' => 'Кейсы', 'link' => '#pricing'],
            ['title' => 'Квиз', 'link' => '#quiz'],
        ] as $index => $item) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'nav_link',
                'title' => $item['title'],
                'link' => $item['link'],
                'sort_order' => $index + 1,
            ]);
        }

        foreach ([
            ['title' => 'Войти', 'link' => '/admin/login', 'button_style' => 'link'],
            ['title' => 'Получить демо', 'link' => '#quiz', 'button_style' => 'primary'],
        ] as $index => $button) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'header_button',
                'title' => $button['title'],
                'link' => $button['link'],
                'button_style' => $button['button_style'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedHero(): void
    {
        $section = $this->section([
            'slug' => 'hero',
            'name' => 'Главный экран',
            'sort_order' => 2,
            'badge_text' => 'Лучший сервис для экспедиторов в России',
            'badge_icon' => $this->icon('badge-star'),
            'title' => 'ЛогистРу — заявки, рейсы и ЭДО в одном кабинете',
            'seo_h1' => 'CRM для экспедиторов: заявки, ЭТрН и контроль рейсов',
            'button_primary_text' => 'Подобрать тариф',
            'button_secondary_text' => 'Посмотреть возможности',
            'extra' => [
                'hint_text' => 'Удобно для экспедитора, просто для перевозчика',
                'hint_icon' => $this->icon('info-circle'),
                'primary_button_icon' => $this->icon('arrow-right'),
                'dashboard_image_alt' => 'Интерфейс ЛогистРу',
                'carousel_delay_ms' => 5000,
                'carousel_slides' => [],
            ],
        ]);

        foreach ([
            'Создавайте заявки',
            'Контролируйте оплаты',
            'Следите за статусом доставки',
            'Формируйте перевозочные документы согласно ФЗ',
        ] as $index => $title) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'bullet',
                'title' => $title,
                'icon' => $this->icon('doc-check-circle'),
                'sort_order' => $index + 1,
            ]);
        }

    }

    private function seedWhy(): void
    {
        $section = $this->section([
            'slug' => 'why',
            'name' => 'Преимущества',
            'sort_order' => 3,
            'title' => 'Преимущества',
            'subtitle' => 'Делаем работу логиста предсказуемой: меньше звонков, чатов и потерянных задач — больше прозрачности.',
        ]);

        foreach ([
            ['icon' => $this->icon('document-fast'), 'title' => 'Договор-заявка за 5 минут', 'description' => 'Автозаполнение реквизитов и шаблоны — оформление в несколько кликов.'],
            ['icon' => $this->icon('chart-bar'), 'title' => 'Аналитика и отчёты', 'description' => 'Следите за прибылью и эффективностью работы с каждым заказчиком.'],
            ['icon' => $this->icon('lifebuoy'), 'title' => 'Тех. поддержка', 'description' => 'Помогаем настроить процессы и оперативно отвечаем на вопросы.'],
            ['icon' => $this->icon('shield-check'), 'title' => 'Безопасность данных', 'description' => 'Серверы в РФ и соответствие требованиям ФЗ №140 от 07.06.2025 г.'],
            ['icon' => $this->icon('document-signed'), 'title' => 'Встроенный ЭДО', 'description' => 'Электронные перевозочные документы внутри сервиса — без дополнительных оплат.'],
        ] as $index => $card) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'card',
                'title' => $card['title'],
                'description' => $card['description'],
                'icon' => $card['icon'],
                'sort_order' => $index + 1,
            ]);
        }

        foreach ([
            ['title' => '−60%', 'subtitle' => 'времени на оформление заявки'],
            ['title' => '100%', 'subtitle' => 'прозрачность статусов рейса'],
            ['title' => '0', 'subtitle' => 'потерянных задач в почте'],
        ] as $index => $stat) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'stat',
                'title' => $stat['title'],
                'subtitle' => $stat['subtitle'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * Replaces the legacy advantages content with the current Figma functional section.
     */
    private function seedFunctional(): void
    {
        $section = LandingSection::query()->where('slug', 'why')->firstOrFail();

        $section->update([
            'name' => 'Функционал',
            'title' => 'Функционал',
            'subtitle' => 'Всё, что нужно экспедитору для работы с заявкой — от оформления и документооборота до аналитики прибыли и проверки контрагентов.',
            'extra' => [
                'quote' => 'Наша главная ценность в том, что мы упрощаем процесс работы с заявкой от Заказчика, делаем его простым и удобным',
                'quote_initials' => 'СА',
                'quote_author' => 'Станислав Аристов',
                'quote_handle' => '@sss_air',
            ],
        ]);

        LandingBlock::query()->where('section_slug', 'why')->delete();

        foreach ([
            ['title' => 'Заявка', 'description' => 'Первый договор-заявка оформляется за считанные минуты. На его основе автоматически заполняются доверенность, транспортная накладная и ЭТрН. Документы отправляются перевозчику через ЭДО или почту.', 'tag' => '3–5 минут на заявку'],
            ['title' => 'Документооборот и банковские выписки', 'description' => 'После доставки заявка попадает в документооборот. Пользователь указывает плановые даты оплат, а сервис напоминает о сроках. Загруженные выписки показывают все платежи в личном кабинете.', 'tag' => 'Контроль оплат'],
            ['title' => 'Зарплата и дополнительные расходы', 'description' => 'Администратор видит расходы на оклады и налоги, а менеджер — только свои показатели. Система автоматически считывает платежи и распределяет их по статьям доходов и расходов.', 'tag' => 'Учёт по ролям'],
            ['title' => 'ЭПД', 'description' => 'Обмен электронными перевозочными документами происходит в несколько кликов. Из входящих поручений экспедитору формируются заказ-заявки и ЭТрН.', 'tag' => 'Обмен в 1 клик'],
            ['title' => 'Прибыль', 'description' => 'Гибкая настройка аналитики и финансов, учёт дополнительных расходов, расчёт прибыли и маржинальности каждой заявки.', 'tag' => 'Маржа по заявке'],
            ['title' => 'Безопасность', 'description' => 'Рейтинг перевозчика в АТИ и проверка контрагентов доступны прямо в личном кабинете, без подключения дополнительных сервисов.', 'tag' => 'Проверка контрагентов'],
        ] as $index => $card) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'card',
                'title' => $card['title'],
                'description' => $card['description'],
                'tag' => $card['tag'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedPlatform(): void
    {
        $section = $this->section([
            'slug' => 'platform',
            'name' => 'Платформа',
            'sort_order' => 4,
            'kicker' => 'Что внутри ЛогистРу',
            'title' => 'Четыре опоры сервиса для экспедитора',
            'description' => 'От первой заявки до электронных перевозочных документов и безопасного хранения данных — всё, что нужно, чтобы вести перевозки без потерь.',
            'extra' => [
                'deadline_kicker' => 'ДЕДЛАЙН',
                'deadline_date' => '1 сентября 2026',
                'deadline_icon' => $this->icon('calendar-alert'),
                'deadline_text' => 'С этой даты транспортный ЭДО становится обязательным. Подключитесь к ЛогистРу заранее — настроим обмен ЭТрН и сопутствующими документами без простоев в работе.',
                'deadline_button_text' => 'Подготовиться к ЭДО',
            ],
        ]);

        $cards = [
            [
                'icon' => $this->icon('clipboard-list'),
                'subtitle' => '01 · Заявки',
                'title' => 'Заявки с клиентами и перевозчиками',
                'description' => 'Экспедитор оформляет в среднем 4–8 заявок в день. Мы автоматизировали заполнение данных по контрагенту — теперь на одну заявку уходит 4–5 минут.',
                'note' => ['icon' => $this->icon('clock'), 'text' => '<strong>4–5 минут</strong> на оформление заявки вместо ручного ввода реквизитов'],
            ],
            [
                'tag' => 'ВАЖНО · с 01.09.2026',
                'icon' => $this->icon('documents'),
                'subtitle' => '02 · ЭДО',
                'title' => 'Электронные перевозочные документы в один клик',
                'description' => 'С 1 сентября 2026 года транспортный ЭДО станет обязательным. В одном окне формируйте, храните и обменивайтесь со всеми участниками:',
                'list' => ['ЭТрН', 'Доверенность на получение груза', 'Заказ-заявка', 'Экспедиторская расписка'],
            ],
            [
                'icon' => $this->icon('server'),
                'subtitle' => '03 · Безопасность',
                'title' => 'Хранение данных на российских серверах',
                'description' => 'Все данные клиентов, перевозчиков и документов размещены в российском облаке в соответствии с требованиями ФЗ №140 от 07.06.2025 г.',
                'pills' => ['ФЗ №140', 'Серверы в РФ', 'Шифрование', 'Резервные копии'],
            ],
            [
                'icon' => $this->icon('route'),
                'subtitle' => '04 · Гибкость',
                'title' => 'Любые сценарии перевозки',
                'description' => 'Поддерживаем разные роли грузоотправителя — это может быть заказчик перевозки, экспедитор при подписании экспедиторской записки или грузополучатель при самовывозе.',
                'roles' => [
                    ['title' => 'Экспедитор', 'subtitle' => 'при подписании экспедиторской записки'],
                    ['title' => 'Грузоотправитель', 'subtitle' => 'когда заказчик перевозки оформляет документы сам'],
                ],
            ],
        ];

        foreach ($cards as $index => $card) {
            $block = $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'card',
                'tag' => $card['tag'] ?? null,
                'icon' => $card['icon'],
                'subtitle' => $card['subtitle'],
                'title' => $card['title'],
                'description' => $card['description'],
                'sort_order' => $index + 1,
            ]);

            if (isset($card['note'])) {
                $this->block([
                    'section_slug' => $section->slug,
                    'block_type' => 'note',
                    'parent_id' => $block->id,
                    'icon' => $card['note']['icon'],
                    'description' => $card['note']['text'],
                    'sort_order' => 1,
                ]);
            }

            foreach ($card['list'] ?? [] as $listIndex => $item) {
                $this->block([
                    'section_slug' => $section->slug,
                    'block_type' => 'list_item',
                    'parent_id' => $block->id,
                    'title' => $item,
                    'icon' => $this->icon('check-circle'),
                    'sort_order' => $listIndex + 1,
                ]);
            }

            foreach ($card['pills'] ?? [] as $pillIndex => $pill) {
                $this->block([
                    'section_slug' => $section->slug,
                    'block_type' => 'pill',
                    'parent_id' => $block->id,
                    'title' => $pill,
                    'sort_order' => $pillIndex + 1,
                ]);
            }

            foreach ($card['roles'] ?? [] as $roleIndex => $role) {
                $this->block([
                    'section_slug' => $section->slug,
                    'block_type' => 'role',
                    'parent_id' => $block->id,
                    'title' => $role['title'],
                    'subtitle' => $role['subtitle'],
                    'sort_order' => $roleIndex + 1,
                ]);
            }
        }
    }

    private function seedFeatures(): void
    {
        $section = $this->section([
            'slug' => 'features',
            'name' => 'Возможности',
            'sort_order' => 5,
            'title' => 'Возможности',
            'subtitle' => 'Полный цикл работы: от первого расчёта стоимости до финансовой отчётности и архива документов.',
        ]);

        foreach ([
            ['icon' => $this->icon('calculator'), 'title' => 'Калькулятор просчёта стоимости направлений', 'description' => 'Быстрый расчёт ставок и маржи по направлениям перед отправкой заявки клиенту.'],
            ['icon' => $this->icon('folder-archive'), 'title' => 'Архив документов', 'description' => 'Все договоры, ТТН, акты и доверенности по рейсам — в одном поиске.'],
            ['icon' => $this->icon('bell'), 'title' => 'Уведомления о сроках оплат', 'description' => 'Напоминания по дебиторке и кредиторке — без просрочек и забытых счетов.'],
            ['icon' => $this->icon('banknotes'), 'title' => 'Финансовые отчёты и банковские выписки', 'description' => 'Сводки по выручке, расходам и сверка с выписками банка в одном окне.'],
            ['icon' => $this->icon('users-card'), 'title' => 'Карточки контрагентов', 'description' => 'Реквизиты, история рейсов и взаиморасчётов по каждому клиенту и перевозчику.'],
            ['icon' => $this->icon('rotes'), 'title' => 'Контроль статусов перевозок по этапам исполнения', 'description' => 'У каждой заявки — своя страница трекинга: события рейса, отметки прибытия и доступ для всех участников.'],
        ] as $index => $card) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'card',
                'title' => $card['title'],
                'description' => $card['description'],
                'icon' => $card['icon'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedPricing(): void
    {
        $section = $this->section([
            'slug' => 'pricing',
            'name' => 'Тарифы',
            'sort_order' => 6,
            'title' => 'Выберите масштаб под ваш парк и поток заявок',
            'subtitle' => 'Прозрачные уровни — начните с малого и перейдите на корпоративный режим, когда вырастете.',
            'extra' => [
                'footnote' => 'Не уверены, что подойдёт?',
                'footnote_link_text' => 'Подобрать тариф через квиз →',
                'footnote_link' => '#quiz',
            ],
        ]);

        $plans = [
            ['title' => 'Старт', 'subtitle' => 'Для малой команды и первых заявок', 'price' => 'от 2 900 ₽', 'description' => 'В тариф включено 3 рабочих места', 'button_text' => 'Подобрать тариф', 'link' => '#quiz', 'button_style' => 'ghost', 'features' => ['Базовые заказы', 'До 3 пользователей', 'Ключевые справочники', 'Поддержка по почте']],
            ['title' => 'Профи', 'subtitle' => 'Для растущей логистики', 'price' => 'от 7 900 ₽', 'description' => 'В тариф включено 10 рабочих мест', 'button_text' => 'Подобрать тариф', 'link' => '#quiz', 'button_style' => 'primary', 'is_highlighted' => true, 'tag' => 'Хит', 'features' => ['Полный цикл заказа', 'Водители и транспорт', 'Стандартные отчёты', 'Ссылка водителю']],
            ['title' => 'Профи+', 'subtitle' => 'Несколько направлений и филиалов', 'price' => 'от 14 900 ₽', 'description' => 'В тариф включено 25 рабочих мест', 'button_text' => 'Подобрать тариф', 'link' => '#quiz', 'button_style' => 'ghost', 'features' => ['Расширенные права', 'Больше интеграций', 'Приоритетная поддержка', 'Кастомные отчёты']],
            ['title' => 'Корпорация', 'subtitle' => 'Индивидуально под компанию', 'price' => 'Запросить расчёт', 'description' => 'Количество мест по договору', 'button_text' => 'Связаться с нами', 'link' => '/pages/contacts', 'button_style' => 'ghost', 'features' => ['Индивидуальные лимиты', 'SSO и безопасность', 'SLA и онбординг', 'Выделенный менеджер']],
        ];

        foreach ($plans as $index => $plan) {
            $block = $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'plan',
                'title' => $plan['title'],
                'subtitle' => $plan['subtitle'],
                'price' => $plan['price'],
                'description' => $plan['description'] ?? null,
                'tag' => $plan['tag'] ?? null,
                'button_text' => $plan['button_text'],
                'link' => $plan['link'] ?? null,
                'button_style' => $plan['button_style'],
                'is_highlighted' => $plan['is_highlighted'] ?? false,
                'sort_order' => $index + 1,
            ]);

            foreach ($plan['features'] as $featureIndex => $feature) {
                $this->block([
                    'section_slug' => $section->slug,
                    'block_type' => 'feature',
                    'parent_id' => $block->id,
                    'title' => $feature,
                    'icon' => $this->icon('check'),
                    'sort_order' => $featureIndex + 1,
                ]);
            }
        }
    }

    private function seedAdditionalOptions(): void
    {
        $section = $this->section([
            'slug' => 'additional_options',
            'name' => 'Дополнительные возможности',
            'sort_order' => 7,
            'kicker' => 'Подключаются отдельно',
            'title' => 'Дополнительные возможности',
            'subtitle' => 'Расширяйте систему по мере роста — подключайте только то, что нужно именно вам, и платите только за это.',
        ]);

        foreach ([
            ['title' => 'Дополнительное рабочее место', 'description' => 'Каждое место сверх тарифного лимита оплачивается отдельно.', 'price' => '1 200 ₽/мес', 'icon' => 'additional-seat'],
            ['title' => 'Дополнительный пакет ЭПД', 'description' => 'Докупите пакет электронных перевозочных документов сверх включённого объёма.', 'price' => 'по пакетам', 'icon' => 'additional-epd'],
            ['title' => 'Дополнительное место в облаке', 'description' => 'Расширьте хранилище для документов и вложений на любой объём.', 'price' => 'по объёму', 'icon' => 'additional-cloud'],
        ] as $index => $option) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'option',
                'title' => $option['title'],
                'description' => $option['description'],
                'price' => $option['price'],
                'icon' => $this->icon($option['icon']),
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedEpdPlatform(): void
    {
        $section = $this->section([
            'slug' => 'epd_platform',
            'name' => 'Платформа ЭПД',
            'anchor' => 'epd-platform',
            'sort_order' => 8,
            'title' => 'Платформа ЭПД',
            'subtitle' => 'Обмен электронными перевозочными документами между участниками грузоперевозок',
            'badge_icon' => $this->icon('epd-platform'),
            'button_primary_text' => 'Подробнее о сервисе',
        ]);

        foreach ([
            ['100', '1 000 ₽', '10 ₽ / документ'],
            ['500', '3 000 ₽', '6 ₽ / документ'],
            ['1 000', '5 000 ₽', '5 ₽ / документ'],
            ['5 000', '20 000 ₽', '4 ₽ / документ'],
            ['10 000', '35 000 ₽', '3,5 ₽ / документ'],
            ['50 000', '150 000 ₽', '3 ₽ / документ'],
            ['100 000', '250 000 ₽', '2,5 ₽ / документ'],
        ] as $index => [$documents, $price, $rate]) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'package',
                'title' => $documents,
                'subtitle' => 'документов',
                'price' => $price,
                'description' => $rate,
                'button_text' => 'Выбрать',
                'link' => '#quiz',
                'button_style' => 'ghost',
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedMobile(): void
    {
        $section = $this->section([
            'slug' => 'mobile',
            'name' => 'Мобильная версия',
            'sort_order' => 10,
            'badge_text' => 'Настройка интерфейса',
            'badge_icon' => $this->icon('sliders'),
            'title' => 'Удобная мобильная версия',
            'description' => 'Создавайте и редактируйте заявки прямо с телефона — без установки приложения, в привычном браузере и с полным функционалом кабинета.',
            'extra' => [
                'pill_left_text' => 'Mobile-first',
                'pill_left_icon' => $this->icon('smartphone'),
                'pill_right_text' => 'Без приложения',
                'pill_right_icon' => $this->icon('browser'),
            ],
        ]);

        foreach ([
            'Создание и редактирование заявок на ходу',
            'Гибкая настройка колонок и фильтров под себя',
            'Те же данные, что и на компьютере — без задержек',
        ] as $index => $title) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'bullet',
                'title' => $title,
                'icon' => $this->icon('check-blue'),
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedGrowth(): void
    {
        $this->section([
            'slug' => 'growth',
            'name' => 'Рост и эффективность',
            'sort_order' => 9,
            'title' => 'Повышайте эффективность и растите вместе с нами',
            'extra' => [
                'lead_prefix' => 'Работа в нашей системе освободит от',
                'lead_highlight' => '30 до 60% времени',
                'lead_suffix' => ', которое вы раньше тратили на составление, редактирование и учёт транспортных документов в таблицах или сторонних сервисах.',
                'paragraph_two' => 'Это время вы сможете направить на поиск новых клиентов и работу с действующими заказчиками.',
                'paragraph_three' => 'Занимайтесь новыми проектами, не отвлекаясь от текущих задач, имея круглосуточный доступ к личному кабинету с любого из ваших устройств.',
                'customer_names' => [
                    ['name' => 'ООО "ГК «ЛОГОС»"'],
                    ['name' => 'АО "УКЗ"'],
                    ['name' => 'ООО "БУГУЛЬМИНСКИЙ СЕЛЬСКОХОЗЯЙСТВЕННЫЙ РЫНОК"'],
                    ['name' => 'ООО "МЕТАЛЛИНВЕСТСПБ"'],
                    ['name' => 'ООО "КЛИМАТ-КОМПЛЕКС"'],
                ],
            ],
        ]);
    }

    private function seedQuiz(): void
    {
        $section = $this->section([
            'slug' => 'quiz',
            'name' => 'Квиз',
            'sort_order' => 12,
            'kicker' => 'Квиз · 1 минута',
            'title' => 'Подберём тариф под вашу логистику',
            'description' => 'Ответьте на 4 коротких вопроса — пришлём подходящий тариф и расчёт за 15 минут в рабочее время.',
            'extra' => [
                'next_button_icon' => $this->icon('arrow-right'),
                'finish_title' => 'Куда прислать расчёт?',
                'finish_description' => 'Оставьте контакты — пришлём подходящий тариф и расчёт в рабочее время.',
                'success_title' => 'Спасибо!',
                'success_description' => 'Мы получили ваши ответы и свяжемся с вами в ближайшее время.',
            ],
        ]);

        $questions = [
            [
                'title' => 'Сколько заявок в месяц?',
                'options' => ['до 50', '50–200', '200–1000', 'более 1000'],
            ],
            [
                'title' => 'Сколько юрлиц и контрагентов в работе?',
                'options' => ['1–3', '4–10', '11–30', 'более 30'],
            ],
            [
                'title' => 'Нужны ли интеграции с ЭДО или банками?',
                'options' => ['Да, обязательно', 'Желательно', 'Пока нет', 'Пока не знаю'],
            ],
            [
                'title' => 'Когда планируете запуск?',
                'options' => ['В этом месяце', 'В ближайшие 3 месяца', 'Позже', 'Только смотрю варианты'],
            ],
        ];

        foreach ($questions as $index => $item) {
            $question = $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'question',
                'title' => $item['title'],
                'sort_order' => $index + 1,
            ]);

            foreach ($item['options'] as $optionIndex => $option) {
                $this->block([
                    'section_slug' => $section->slug,
                    'block_type' => 'option',
                    'parent_id' => $question->id,
                    'title' => $option,
                    'sort_order' => $optionIndex + 1,
                ]);
            }
        }
    }

    private function seedFaq(): void
    {
        $section = $this->section([
            'slug' => 'faq',
            'name' => 'FAQ',
            'sort_order' => 13,
            'title' => 'Частые вопросы',
            'extra' => [
                'toggle_icon' => $this->icon('chevron-down'),
            ],
        ]);

        foreach ([
            ['title' => 'Насколько безопасна ссылка водителю?', 'description' => 'Ссылка одноразовая и ограничена по времени. Водитель видит только данные конкретного рейса.'],
            ['title' => 'Где хранятся данные?', 'description' => 'На серверах в России в соответствии с требованиями законодательства.'],
            ['title' => 'Есть ли мобильная версия?', 'description' => 'Да, кабинет полностью адаптирован под смартфоны без установки приложения.'],
            ['title' => 'Какие интеграции уже есть?', 'description' => 'Поддерживаем обмен с банками, ЭДО-провайдерами и популярными CRM.'],
            ['title' => 'Можно ли попробовать бесплатно?', 'description' => 'Да, проведём демо и дадим тестовый доступ на 14 дней.'],
        ] as $index => $item) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'faq',
                'title' => $item['title'],
                'description' => $item['description'],
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function seedFinalCta(): void
    {
        $this->section([
            'slug' => 'final_cta',
            'name' => 'Финальный CTA',
            'sort_order' => 14,
            'title' => 'Подключите ЛОГИСТ этой неделе',
            'description' => 'Демо за 30 минут, настройка под ваши процессы, понятный счёт. Без долгих внедрений.',
            'button_primary_text' => 'Подобрать тариф',
            'button_primary_url' => '#pricing',
            'button_secondary_text' => 'Получить демо',
            'button_secondary_url' => '#quiz',
        ]);
    }

    private function seedFooter(): void
    {
        $section = $this->section([
            'slug' => 'footer',
            'name' => 'Подвал',
            'sort_order' => 15,
            'description' => 'Лучший сервис для экспедиторов в России. Заявки, ЭДО и рейсы в одном кабинете.',
            'extra' => [
                'logo_icon' => $this->icon('brand-logo'),
                'brand_name' => 'ЛогистРу',
                'copyright' => '© 2026 ЛогистРу. Все права защищены.',
                'tagline' => 'Сделано для логистов',
            ],
        ]);

        $columns = [
            'product' => [
                'title' => 'Продукт',
                'links' => [
                    ['title' => 'Возможности', 'link' => '#features'],
                    ['title' => 'Тарифы', 'link' => '#pricing'],
                    ['title' => 'Кейсы', 'link' => '#pricing'],
                    ['title' => 'Подобрать тариф', 'link' => '#pricing'],
                ],
            ],
            'company' => [
                'title' => 'Компания',
                'links' => [
                    ['title' => 'О нас', 'link' => '#about'],
                    ['title' => 'Блог', 'link' => '#blog'],
                    ['title' => 'Документы', 'link' => '#docs'],
                    ['title' => 'Политика конфиденциальности', 'link' => '/pages/privacy-policy'],
                ],
            ],
            'contacts' => [
                'title' => 'Контакты',
                'links' => [
                    ['title' => 'hello@логист.ру', 'link' => 'mailto:hello@логист.ру', 'icon' => $this->icon('mail')],
                    ['title' => '+7 (495) 000-00-00', 'link' => 'tel:+74950000000', 'icon' => $this->icon('phone')],
                ],
            ],
        ];

        $sort = 1;
        foreach ($columns as $key => $column) {
            $parent = $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'footer_column',
                'title' => $column['title'],
                'extra' => ['column' => $key],
                'sort_order' => $sort++,
            ]);

            foreach ($column['links'] as $linkIndex => $link) {
                $this->block([
                    'section_slug' => $section->slug,
                    'block_type' => 'footer_link',
                    'parent_id' => $parent->id,
                    'title' => $link['title'],
                    'link' => $link['link'],
                    'icon' => $link['icon'] ?? null,
                    'sort_order' => $linkIndex + 1,
                ]);
            }
        }
    }

    private function seedDriverCabinet(): void
    {
        $section = $this->section([
            'slug' => 'driver_cabinet',
            'name' => 'Личный кабинет водителя',
            'sort_order' => 11,
            'badge_text' => 'Для водителя',
            'badge_icon' => $this->icon('user-driver'),
            'title' => 'Личный кабинет водителя',
            'description' => 'Водитель видит рейсы, статусы и ключевые документы в одном окне. Всё под рукой без лишних звонков и чатов.',
            'extra' => [
                'pill_left_text' => 'Ссылка в один клик',
                'pill_left_icon' => $this->icon('link'),
                'pill_right_text' => 'Статусы в реальном времени',
                'pill_right_icon' => $this->icon('clock'),
            ],
        ]);

        foreach ([
            'Просмотр назначенных рейсов и адресов загрузки/выгрузки',
            'Подтверждение этапов перевозки прямо с телефона',
            'Доступ к документам по рейсу без установки приложения',
        ] as $index => $title) {
            $this->block([
                'section_slug' => $section->slug,
                'block_type' => 'bullet',
                'title' => $title,
                'icon' => $this->icon('check-blue'),
                'sort_order' => $index + 1,
            ]);
        }
    }
}
