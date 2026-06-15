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
                        ->getUploadedFileUsing(static::uploadPreview(...))
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
                        ->getUploadedFileUsing(static::uploadPreview(...))
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
                        ->getUploadedFileUsing(static::uploadPreview(...))
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
                        ->getUploadedFileUsing(static::uploadPreview(...))
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
                ->description('Краткое описание для AI-ассистентов и файл /llms.txt.')
                ->schema([
                    Textarea::make('ai_site_summary')
                        ->label('Краткое описание для AI')
                        ->rows(4)
                        ->maxLength(1000)
                        ->placeholder(SiteSetting::defaultAiSiteSummary())
                        ->helperText('Meta abstract и цитата в /llms.txt.')
                        ->columnSpanFull(),
                    Textarea::make('llms_txt_extra')
                        ->label('Дополнительный блок в llms.txt')
                        ->rows(10)
                        ->placeholder(SiteSetting::defaultLlmsTxtExtra())
                        ->helperText('Markdown: возможности, тарифы, инструкции.')
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
                ->description('Параметры исходящей почты. Если SMTP не задан — используется конфигурация из .env на сервере.')
                ->schema([
                    TextInput::make('mail_host')
                        ->label('SMTP-сервер')
                        ->maxLength(255)
                        ->placeholder('24logist.ru')
                        ->helperText('Хост SMTP из панели хостинга или проверки (например 24logist.ru, mail.24logist.ru). Порт 465 + SSL.')
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
                            'smtps' => 'SSL (smtps, обычно 465)',
                            'smtp' => 'STARTTLS (smtp, обычно 587)',
                        ])
                        ->default('smtps')
                        ->native(false),
                    TextInput::make('mail_username')
                        ->label('Логин (email)')
                        ->email()
                        ->maxLength(255)
                        ->placeholder('info@24logist.ru')
                        ->columnSpanFull(),
                    TextInput::make('mail_password')
                        ->label('Пароль')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->placeholder('Пароль приложения SMTP')
                        ->helperText('Оставьте пустым, чтобы не менять сохранённый пароль.')
                        ->columnSpanFull(),
                    Toggle::make('mail_verify_ssl')
                        ->label('Проверять SSL-сертификат SMTP')
                        ->default(true)
                        ->helperText('Отключите только если хостинг выдаёт самоподписанный сертификат и соединение падает с ошибкой SSL.')
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
