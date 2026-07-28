<?php

namespace App\Filament\Clusters\Landing\Resources\SiteSettings\Schemas;

use App\Models\SiteSetting;
use App\Support\FilamentUploadPreview;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

final class GeneralSiteSettingForm
{
    /**
     * @return array<int, mixed>
     */
    public static function components(): array
    {
        return [
            Tabs::make('site_settings_tabs')
                ->tabs([
                    Tab::make('icons')
                        ->label('Иконки')
                        ->icon('heroicon-o-photo')
                        ->schema(self::iconsTab()),
                    Tab::make('seo')
                        ->label('SEO')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema(self::seoTab()),
                    Tab::make('blog')
                        ->label('Блог')
                        ->icon('heroicon-o-newspaper')
                        ->schema(self::blogTab()),
                    Tab::make('telegram_popup')
                        ->label('Telegram-окно')
                        ->icon('heroicon-o-paper-airplane')
                        ->schema(self::telegramPopupTab()),
                    Tab::make('organization')
                        ->label('Компания')
                        ->icon('heroicon-o-building-office-2')
                        ->schema(self::organizationTab()),
                    Tab::make('promotion')
                        ->label('Продвижение')
                        ->icon('heroicon-o-globe-alt')
                        ->schema(self::promotionTab()),
                    Tab::make('mail')
                        ->label('Почта')
                        ->icon('heroicon-o-envelope')
                        ->schema(self::mailTab()),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function iconsTab(): array
    {
        return [
            Section::make('Иконки сайта')
                ->description('Favicon, Apple Touch Icon и PWA-иконки.')
                ->schema([
                    FileUpload::make('favicon_path')
                        ->label('Favicon')
                        ->disk('public')
                        ->directory('site/favicon')
                        ->visibility('public')
                        ->imagePreviewHeight('64')
                        ->maxFiles(1)
                        ->maxSize(1024)
                        ->acceptedFileTypes([
                            'image/svg+xml',
                            'image/png',
                            'image/x-icon',
                            'image/vnd.microsoft.icon',
                            'image/webp',
                        ])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(self::uploadPreview(...))
                        ->helperText('Иконка вкладки браузера. SVG, PNG, ICO или WebP до 1 МБ. Пусто — images/favicon.svg.')
                        ->columnSpanFull(),
                    FileUpload::make('apple_touch_icon_path')
                        ->label('Apple Touch Icon (iOS)')
                        ->disk('public')
                        ->directory('site/apple-touch-icon')
                        ->visibility('public')
                        ->imagePreviewHeight('64')
                        ->maxFiles(1)
                        ->maxSize(512)
                        ->acceptedFileTypes([
                            'image/png',
                            'image/jpeg',
                        ])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(self::uploadPreview(...))
                        ->helperText('PNG или JPEG 180×180 px для «Добавить на экран» в iOS.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function seoTab(): array
    {
        return [
            Section::make('Главная страница')
                ->description('Title, description и Open Graph. Используются как значения по умолчанию для CMS-страниц.')
                ->schema([
                    TextInput::make('seo_meta_title')
                        ->label('Meta title')
                        ->maxLength(70)
                        ->placeholder('ЛогистРу — платформа для логистики')
                        ->helperText('Тег <title>. Рекомендуется до 60–70 символов.'),
                    Textarea::make('og_description')
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder('CRM для экспедиторов: заявки, рейсы, ЭДО и документы.')
                        ->helperText('Описание в Google/Яндекс и в соцсетях. 120–160 символов.')
                        ->columnSpanFull(),
                    Textarea::make('seo_keywords')
                        ->label('Meta keywords')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Через запятую. Необязательно.')
                        ->columnSpanFull(),
                    TextInput::make('og_title')
                        ->label('Open Graph — заголовок')
                        ->maxLength(255)
                        ->placeholder('ЛогистРу — платформа для логистики')
                        ->helperText('Необязательно. Пусто — Meta title или Hero.'),
                    FileUpload::make('og_image_path')
                        ->label('Open Graph — изображение')
                        ->disk('public')
                        ->directory('site/og')
                        ->visibility('public')
                        ->image()
                        ->imagePreviewHeight('120')
                        ->maxFiles(1)
                        ->maxSize(4096)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(self::uploadPreview(...))
                        ->helperText('1200×630 px. Главная и fallback для CMS.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function blogTab(): array
    {
        return [
            Section::make('Главная страница блога')
                ->description('Тексты hero-блока на странице /blog и fallback для SEO-описания блога.')
                ->schema([
                    TextInput::make('blog_kicker')
                        ->label('Плашка над заголовком')
                        ->maxLength(255)
                        ->placeholder('Блог 24Logist')
                        ->helperText('Небольшой текст над H1.'),
                    TextInput::make('blog_title')
                        ->label('H1 блога')
                        ->maxLength(255)
                        ->placeholder('Практика цифровой логистики')
                        ->helperText('Главный заголовок страницы /blog.'),
                    Textarea::make('blog_description')
                        ->label('Описание под заголовком')
                        ->rows(3)
                        ->maxLength(700)
                        ->placeholder('Разбираем перевозки, автоматизацию, документооборот, контроль рейсов и управленческие процессы без лишней теории.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function organizationTab(): array
    {
        return [
            Section::make('Реквизиты и контакты')
                ->description('Schema.org Organization, страница контактов, llms.txt.')
                ->schema([
                    TextInput::make('org_brand_name')
                        ->label('Бренд / название сервиса')
                        ->maxLength(255)
                        ->placeholder('ЛогистРу'),
                    TextInput::make('org_legal_name')
                        ->label('Юридическое название')
                        ->maxLength(255)
                        ->placeholder('ООО «Энерви Групп»')
                        ->columnSpanFull(),
                    TextInput::make('org_email')
                        ->label('Публичный email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('org_phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(255),
                    FileUpload::make('org_logo_path')
                        ->label('Логотип (Schema.org)')
                        ->disk('public')
                        ->directory('site/org')
                        ->visibility('public')
                        ->image()
                        ->imagePreviewHeight('80')
                        ->maxFiles(1)
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(self::uploadPreview(...))
                        ->columnSpanFull(),
                    TextInput::make('org_street_address')
                        ->label('Улица, дом')
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('org_address_locality')
                        ->label('Город')
                        ->maxLength(255),
                    TextInput::make('org_address_region')
                        ->label('Регион')
                        ->maxLength(255),
                    TextInput::make('org_postal_code')
                        ->label('Индекс')
                        ->maxLength(20),
                    TextInput::make('org_address_country')
                        ->label('Страна (ISO)')
                        ->maxLength(2)
                        ->default('RU'),
                    TextInput::make('org_inn')
                        ->label('ИНН')
                        ->maxLength(12),
                    TextInput::make('org_ogrn')
                        ->label('ОГРН')
                        ->maxLength(15),
                    Textarea::make('org_same_as')
                        ->label('Соцсети (sameAs)')
                        ->rows(3)
                        ->helperText('По одному URL на строку.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Twitter / X')
                ->schema([
                    TextInput::make('twitter_site')
                        ->label('Twitter site')
                        ->maxLength(255)
                        ->placeholder('@logistru'),
                    TextInput::make('twitter_creator')
                        ->label('Twitter creator')
                        ->maxLength(255),
                ])
                ->columns(2)
                ->collapsed()
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function telegramPopupTab(): array
    {
        return [
            Section::make('Окно подписки на Telegram-канал')
                ->description('Показывается один раз за сеанс после загрузки главной страницы.')
                ->schema([
                    Toggle::make('telegram_popup_enabled')
                        ->label('Окно включено')
                        ->default(false)
                        ->live(),
                    TextInput::make('telegram_popup_channel_url')
                        ->label('Ссылка на канал для десктопа')
                        ->url()
                        ->maxLength(2048)
                        ->placeholder('https://telegram.me/logistru24')
                        ->required(fn ($get): bool => (bool) $get('telegram_popup_enabled')),
                    TextInput::make('telegram_popup_mobile_url')
                        ->label('Ссылка для мобильных устройств')
                        ->maxLength(2048)
                        ->placeholder('tg://resolve?domain=logistru24')
                        ->default('tg://resolve?domain=logistru24')
                        ->rule('regex:/^tg:\/\/resolve\?domain=[A-Za-z0-9_]+$/')
                        ->helperText('Открывает канал сразу в приложении Telegram. Формат: tg://resolve?domain=username')
                        ->required(fn ($get): bool => (bool) $get('telegram_popup_enabled')),
                    TextInput::make('telegram_popup_badge')
                        ->label('Текст плашки')
                        ->maxLength(100)
                        ->default('Telegram-канал'),
                    TextInput::make('telegram_popup_title')
                        ->label('Заголовок')
                        ->maxLength(255)
                        ->default('Будьте в курсе обновлений'),
                    Textarea::make('telegram_popup_description')
                        ->label('Описание')
                        ->rows(4)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    TextInput::make('telegram_popup_button_text')
                        ->label('Текст кнопки')
                        ->maxLength(100)
                        ->default('Подписаться на канал'),
                    TextInput::make('telegram_popup_dismiss_text')
                        ->label('Текст кнопки закрытия')
                        ->maxLength(100)
                        ->default('Не сейчас'),
                    TextInput::make('telegram_popup_show_delay')
                        ->label('Показать через, секунд')
                        ->numeric()
                        ->minValue(45)
                        ->maxValue(86400)
                        ->default(45)
                        ->helperText('Не менее 45 секунд. Окно также может появиться раньше после достаточной прокрутки страницы.')
                        ->required(),
                    TextInput::make('telegram_popup_scroll_percent')
                        ->label('Показать после прокрутки, %')
                        ->numeric()
                        ->minValue(25)
                        ->maxValue(90)
                        ->default(55)
                        ->helperText('На мобильных устройствах используется минимум 65%.')
                        ->required(),
                    TextInput::make('telegram_popup_auto_close_delay')
                        ->label('Автоматически закрыть через, секунд')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(86400)
                        ->default(0)
                        ->helperText('0 — не закрывать автоматически.')
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function promotionTab(): array
    {
        return [
            Section::make('Верификация')
                ->description('Коды из Google Search Console и Яндекс.Вебмастер.')
                ->schema([
                    TextInput::make('google_site_verification')
                        ->label('Google site verification')
                        ->maxLength(255),
                    TextInput::make('yandex_site_verification')
                        ->label('Yandex verification')
                        ->maxLength(255),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('AI-поиск и llms.txt')
                ->description('Краткое описание для метаданных и полное содержимое файла /llms.txt.')
                ->schema([
                    Textarea::make('ai_site_summary')
                        ->label('Краткое описание для AI')
                        ->rows(4)
                        ->maxLength(1000)
                        ->placeholder(SiteSetting::defaultAiSiteSummary())
                        ->helperText('Meta abstract для AI-ассистентов.')
                        ->columnSpanFull(),
                    Textarea::make('llms_txt_extra')
                        ->label('Полное содержимое llms.txt')
                        ->rows(20)
                        ->placeholder(SiteSetting::defaultLlmsTxtExtra())
                        ->helperText('Markdown. В /llms.txt публикуется только этот текст — без автоматически добавляемых разделов.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function mailTab(): array
    {
        return [
            Section::make('SMTP-сервер')
                ->description('Параметры исходящей почты. Все письма отправляются только через эти настройки.')
                ->schema([
                    TextInput::make('mail_host')
                        ->label('SMTP-сервер')
                        ->maxLength(255)
                        ->placeholder('24logist.ru')
                        ->helperText('Как в панели хостинга. Обычно: 24logist.ru, порт 465, шифрование SSL. Соединение шифруется; на shared-хостинге PHP часто не может проверить SMTP-сертификат — это нормально.')
                        ->columnSpanFull(),
                    TextInput::make('mail_port')
                        ->label('Порт')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(65535)
                        ->default(465),
                    Select::make('mail_encryption')
                        ->label('Шифрование')
                        ->options([
                            'ssl' => 'SSL (порт 465)',
                            'tls' => 'TLS / STARTTLS (порт 587)',
                            'none' => 'Без шифрования',
                        ])
                        ->default('ssl')
                        ->native(false),
                    TextInput::make('mail_username')
                        ->label('Логин (email)')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('info@24logist.ru')
                        ->columnSpanFull(),
                    Placeholder::make('mail_password_status')
                        ->label('Статус пароля')
                        ->content(function (?SiteSetting $record): string {
                            if ($record?->hasMailPassword()) {
                                return '✓ Пароль сохранён в базе. В поле ниже пароль намеренно не показывается — так безопаснее.';
                            }

                            return '✗ Пароль не задан. Введите ниже и нажмите «Сохранить» внизу страницы.';
                        })
                        ->columnSpanFull(),
                    TextInput::make('mail_password')
                        ->label('Новый пароль (только для смены)')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->dehydrated()
                        ->placeholder(fn (?SiteSetting $record): string => $record?->hasMailPassword()
                            ? 'Оставьте пустым, если не меняете пароль'
                            : 'Пароль почтового ящика')
                        ->helperText('После ввода нажмите «Сохранить». Появится уведомление «Пароль почты сохранён».')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Отправитель')
                ->schema([
                    TextInput::make('mail_from_address')
                        ->label('С какого email уходят письма')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('info@24logist.ru'),
                    TextInput::make('mail_from_name')
                        ->label('Имя отправителя')
                        ->maxLength(255)
                        ->placeholder('ЛогистРу'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Письма по событиям')
                ->description('Автоматические письма при отправке квиза или формы контактов.')
                ->schema([
                    Section::make('Менеджерам: новая заявка')
                        ->schema([
                            Toggle::make('leads_notifications_enabled')
                                ->label('Включено')
                                ->default(true),
                            Textarea::make('leads_notification_emails')
                                ->label('Кому отправлять')
                                ->rows(2)
                                ->placeholder('info@24logist.ru, sales@24logist.ru')
                                ->helperText('Через запятую. Пусто — публичный email компании.'),
                            TextInput::make('leads_notification_subject')
                                ->label('Тема письма')
                                ->maxLength(255)
                                ->placeholder(SiteSetting::defaultLeadsNotificationSubject())
                                ->columnSpanFull(),
                            Textarea::make('leads_notification_body')
                                ->label('Текст письма')
                                ->rows(10)
                                ->placeholder(SiteSetting::defaultLeadsNotificationBody())
                                ->helperText('Подстановки: {name}, {email}, {phone}, {type}, {plan}, {brand}, {company_email}, {company_phone}, {admin_url}')
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->compact(),
                    Section::make('Клиенту: приветственное письмо')
                        ->schema([
                            Toggle::make('leads_welcome_enabled')
                                ->label('Включено')
                                ->default(true)
                                ->helperText('Отправляется на email из заявки. Если email не указан — письмо не уходит.'),
                            TextInput::make('leads_welcome_subject')
                                ->label('Тема письма')
                                ->maxLength(255)
                                ->placeholder(SiteSetting::defaultLeadsWelcomeSubject())
                                ->columnSpanFull(),
                            Textarea::make('leads_welcome_body')
                                ->label('Текст письма')
                                ->rows(10)
                                ->placeholder(SiteSetting::defaultLeadsWelcomeBody())
                                ->helperText('Подстановки: {name}, {email}, {phone}, {type}, {plan}, {brand}, {company_email}, {company_phone}')
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->compact(),
                ])
                ->columnSpanFull(),
            Placeholder::make('mail_test_hint')
                ->label('Проверка')
                ->content('Сохраните настройки, затем нажмите «Тестовое письмо» вверху страницы.')
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  string|array<string, string>|null  $storedFileNames
     * @return array{name: string, size: int, type: ?string, url: ?string}|null
     */
    protected static function uploadPreview(FileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        return FilamentUploadPreview::resolve($component, $file, $storedFileNames);
    }
}
