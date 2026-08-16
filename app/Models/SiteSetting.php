<?php

namespace App\Models;

use App\Support\LandingMedia;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'favicon_path',
        'apple_touch_icon_path',
        'og_title',
        'og_description',
        'og_image_path',
        'seo_meta_title',
        'seo_keywords',
        'org_brand_name',
        'org_legal_name',
        'org_email',
        'org_phone',
        'org_logo_path',
        'org_street_address',
        'org_address_locality',
        'org_address_region',
        'org_postal_code',
        'org_address_country',
        'org_inn',
        'org_ogrn',
        'org_same_as',
        'twitter_site',
        'twitter_creator',
        'google_site_verification',
        'yandex_site_verification',
        'ai_site_summary',
        'llms_txt_extra',
        'blog_kicker',
        'blog_title',
        'blog_description',
        'telegram_popup_enabled',
        'telegram_popup_badge',
        'telegram_popup_title',
        'telegram_popup_description',
        'telegram_popup_button_text',
        'telegram_popup_dismiss_text',
        'telegram_popup_channel_url',
        'telegram_popup_mobile_url',
        'telegram_popup_show_delay',
        'telegram_popup_scroll_percent',
        'telegram_popup_auto_close_delay',
        'leads_notifications_enabled',
        'leads_notification_emails',
        'leads_notification_subject',
        'leads_notification_body',
        'leads_welcome_enabled',
        'leads_welcome_subject',
        'leads_welcome_body',
        'mail_host',
        'mail_port',
        'mail_encryption',
        'mail_username',
        'mail_password',
        'mail_verify_ssl',
        'mail_from_address',
        'mail_from_name',
        'yandex_metrika_enabled',
        'yandex_metrika_counter_id',
        'yandex_metrika_webvisor',
        'yandex_metrika_clickmap',
        'yandex_metrika_track_links',
        'yandex_metrika_accurate_track_bounce',
        'google_analytics_enabled',
        'google_analytics_measurement_id',
    ];

    protected function casts(): array
    {
        return [
            'leads_notifications_enabled' => 'boolean',
            'telegram_popup_enabled' => 'boolean',
            'telegram_popup_show_delay' => 'integer',
            'telegram_popup_scroll_percent' => 'integer',
            'telegram_popup_auto_close_delay' => 'integer',
            'leads_welcome_enabled' => 'boolean',
            'mail_port' => 'integer',
            'mail_password' => 'encrypted',
            'mail_verify_ssl' => 'boolean',
            'yandex_metrika_enabled' => 'boolean',
            'yandex_metrika_webvisor' => 'boolean',
            'yandex_metrika_clickmap' => 'boolean',
            'yandex_metrika_track_links' => 'boolean',
            'yandex_metrika_accurate_track_bounce' => 'boolean',
            'google_analytics_enabled' => 'boolean',
        ];
    }

    public static function defaultAiSiteSummary(): string
    {
        return 'ЛогистРу — облачная CRM для экспедиторов и логистических компаний в России. '
            .'Сервис объединяет заявки на перевозку, контроль рейсов, работу с контрагентами и водителями, '
            .'а также встроенный транспортный ЭДО (ЭТрН и сопутствующие документы). '
            .'Данные хранятся на серверах в РФ; тарифы от 2 900 ₽/мес.';
    }

    public static function defaultLlmsTxtExtra(): string
    {
        return <<<'MD'
## О продукте

ЛогистРу (24logist.ru) — SaaS-платформа для экспедиторов: заявки, рейсы, контрагенты, водители, отчёты и встроенный транспортный ЭДО.

**Аудитория:** экспедиторские и транспортные компании в России.

**Ключевые возможности:**
- Оформление заявок и договоров с автозаполнением реквизитов
- Контроль статусов рейсов и оплат
- Кабинет водителя по ссылке
- ЭДО: ЭТрН, заказ-заявка, экспедиторская расписка, доверенность на получение груза
- Хранение данных на серверах в РФ (ФЗ №140)
- Мобильная версия для работы в дороге

## Тарифы (ориентиры)

- **Старт** — от 2 900 ₽/мес, 3 рабочих места
- **Профи** — от 7 900 ₽/мес, 10 рабочих мест
- **Профи+** — от 14 900 ₽/мес, 25 рабочих мест
- **Корпорация** — индивидуальный расчёт

Подбор тарифа: квиз на главной странице или запрос через форму контактов.
MD;
    }

    public static function defaultLeadsWelcomeSubject(): string
    {
        return 'Спасибо за заявку, {name}!';
    }

    public static function defaultLeadsNotificationSubject(): string
    {
        return 'Новая заявка: {type} — {name}';
    }

    public static function defaultLeadsNotificationBody(): string
    {
        return <<<'TEXT'
Новая заявка с сайта {brand}

Тип: {type}
Имя: {name}
Телефон: {phone}
Email: {email}
Тариф: {plan}

Открыть в админке: {admin_url}
TEXT;
    }

    public static function defaultLeadsWelcomeBody(): string
    {
        return <<<'TEXT'
Здравствуйте, {name}!

Мы получили вашу заявку на сайте {brand}. Менеджер свяжется с вами в рабочее время.

Ваш телефон: {phone}
Тип заявки: {type}

С уважением,
Команда {brand}
{company_email} · {company_phone}
TEXT;
    }

    public function hasMailPassword(): bool
    {
        return filled($this->attributes['mail_password'] ?? null);
    }

    public static function instance(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'org_brand_name' => 'ЛогистРу',
                'org_legal_name' => 'Общество с ограниченной ответственностью «Энерви Групп»',
                'org_email' => 'info@24logist.ru',
                'org_phone' => '+7 (495) 109-25-44',
                'org_street_address' => 'ул. Мира, д. 4, помещ. 3',
                'org_address_locality' => 'Подольск',
                'org_address_region' => 'Московская область',
                'org_postal_code' => '142103',
                'org_address_country' => 'RU',
                'org_inn' => '5074081476',
                'org_ogrn' => '1235000051824',
                'ai_site_summary' => static::defaultAiSiteSummary(),
                'llms_txt_extra' => static::defaultLlmsTxtExtra(),
                'blog_kicker' => 'Блог 24Logist',
                'blog_title' => 'Практика цифровой логистики',
                'blog_description' => 'Разбираем перевозки, автоматизацию, документооборот, контроль рейсов и управленческие процессы без лишней теории.',
                'telegram_popup_enabled' => false,
                'telegram_popup_badge' => 'Telegram-канал',
                'telegram_popup_title' => 'Будьте в курсе обновлений',
                'telegram_popup_description' => 'В канале ЛогистРу — новости об изменениях в транспортном ЭДО, обновления сервиса и полезные материалы для экспедиторов.',
                'telegram_popup_button_text' => 'Подписаться на канал',
                'telegram_popup_dismiss_text' => 'Не сейчас',
                'telegram_popup_channel_url' => 'https://telegram.me/logistru24',
                'telegram_popup_mobile_url' => 'tg://resolve?domain=logistru24',
                'telegram_popup_show_delay' => 45,
                'telegram_popup_scroll_percent' => 55,
                'telegram_popup_auto_close_delay' => 0,
                'leads_notifications_enabled' => true,
                'leads_notification_emails' => 'info@24logist.ru',
                'leads_notification_subject' => static::defaultLeadsNotificationSubject(),
                'leads_notification_body' => static::defaultLeadsNotificationBody(),
                'leads_welcome_enabled' => true,
                'leads_welcome_subject' => static::defaultLeadsWelcomeSubject(),
                'leads_welcome_body' => static::defaultLeadsWelcomeBody(),
                'mail_port' => 465,
                'mail_encryption' => 'smtps',
                'mail_verify_ssl' => true,
                'mail_from_address' => 'info@24logist.ru',
                'mail_from_name' => 'ЛогистРу',
                'yandex_metrika_enabled' => false,
                'yandex_metrika_webvisor' => true,
                'yandex_metrika_clickmap' => true,
                'yandex_metrika_track_links' => true,
                'yandex_metrika_accurate_track_bounce' => true,
                'google_analytics_enabled' => false,
            ],
        );
    }

    protected static function booted(): void
    {
        static::saving(function (self $settings): void {
            $settings->favicon_path = LandingMedia::normalizePath($settings->favicon_path);
            $settings->apple_touch_icon_path = LandingMedia::normalizePath($settings->apple_touch_icon_path);
            $settings->og_image_path = LandingMedia::normalizePath($settings->og_image_path);
            $settings->org_logo_path = LandingMedia::normalizePath($settings->org_logo_path);
        });
    }
}
