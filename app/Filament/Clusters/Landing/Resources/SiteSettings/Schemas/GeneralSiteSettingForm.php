<?php

namespace App\Filament\Clusters\Landing\Resources\SiteSettings\Schemas;

use App\Filament\Clusters\Landing\Resources\SiteSettings\Pages\EditGeneralSiteSetting;
use App\Models\SiteSetting;
use App\Services\LlmsTxtService;
use App\Support\FilamentUploadPreview;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
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
                    Tab::make('community')
                        ->label('Сообщество')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema(self::communityTab()),
                    Tab::make('telegram_popup')
                        ->label('Telegram-окно')
                        ->icon('heroicon-o-paper-airplane')
                        ->schema(self::telegramPopupTab()),
                    Tab::make('epd_popup')
                        ->label('Баннеры')
                        ->icon('heroicon-o-document-text')
                        ->schema(self::epdPopupTab()),
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
    private static function communityTab(): array
    {
        return [
            Section::make('Сообщество 24Logist')
                ->description('Управление доступностью публичного раздела /community.')
                ->schema([
                    Toggle::make('community_enabled')
                        ->label('Включить раздел сообщества')
                        ->default(false)
                        ->helperText('Показывает ссылки в шапке и подвале и открывает публичные страницы сообщества.'),
                    Toggle::make('community_max_enabled')
                        ->label('Включить авторизацию через MAX')
                        ->default(false)
                        ->helperText('Включайте только после настройки бота, Mini App и webhook в MAX.'),
                    Toggle::make('community_vk_enabled')
                        ->label('Включить авторизацию через VK ID')
                        ->default(false)
                        ->helperText('Включайте после создания приложения VK ID и регистрации точного Redirect URI.'),
                ])
                ->columns(1)
                ->columnSpanFull(),
            Section::make('Авторизация через Telegram')
                ->description('Значения хранятся в базе в зашифрованном виде. Секреты после сохранения повторно не показываются.')
                ->schema([
                    TextInput::make('community_telegram_client_id')
                        ->label('Client ID')
                        ->maxLength(500),
                    TextInput::make('community_telegram_redirect_uri')
                        ->label('Redirect URI')
                        ->url()
                        ->maxLength(2048)
                        ->placeholder('https://24logist.ru/community/auth/telegram/callback'),
                    Placeholder::make('community_telegram_client_secret_status')
                        ->label('Client Secret')
                        ->content(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_telegram_client_secret') ? '***' : 'Не настроен'),
                    TextInput::make('community_telegram_client_secret')
                        ->label('Новый Client Secret')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->dehydrated()
                        ->placeholder(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_telegram_client_secret') ? '***' : '')
                        ->helperText('Оставьте пустым, чтобы сохранить действующее значение.'),
                    Placeholder::make('community_telegram_bot_token_status')
                        ->label('Bot Token')
                        ->content(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_telegram_bot_token') ? '***' : 'Не настроен'),
                    TextInput::make('community_telegram_bot_token')
                        ->label('Новый Bot Token')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->dehydrated()
                        ->placeholder(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_telegram_bot_token') ? '***' : '')
                        ->helperText('Нужен для доставки уведомлений. Оставьте пустым, чтобы сохранить действующее значение.'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Авторизация через VK ID')
                ->description('Для серверного обмена кода выберите в VK ID уровень «Конфиденциальное» и укажите IP бэкенда. Секреты хранятся в базе в зашифрованном виде.')
                ->schema([
                    TextInput::make('community_vk_client_id')
                        ->label('ID приложения')
                        ->maxLength(500)
                        ->helperText('ID приложения из кабинета VK ID.'),
                    Placeholder::make('community_vk_client_secret_status')
                        ->label('Защищённый ключ')
                        ->content(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_vk_client_secret') ? '***' : 'Не настроен'),
                    TextInput::make('community_vk_client_secret')
                        ->label('Новый защищённый ключ')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->dehydrated()
                        ->placeholder(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_vk_client_secret') ? '***' : '')
                        ->helperText('Параметр client_secret из раздела «Ключи доступа». Оставьте пустым, чтобы сохранить действующее значение.'),
                    Placeholder::make('community_vk_service_token_status')
                        ->label('Сервисный ключ доступа')
                        ->content(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_vk_service_token') ? '***' : 'Не настроен'),
                    TextInput::make('community_vk_service_token')
                        ->label('Новый сервисный ключ доступа')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->dehydrated()
                        ->placeholder(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_vk_service_token') ? '***' : '')
                        ->helperText('Обязателен как service_token для конфиденциального приложения с обменом кода на бэкенде. Оставьте пустым, чтобы сохранить действующее значение.'),
                    TextInput::make('community_vk_redirect_uri')
                        ->label('Redirect URI')
                        ->url()
                        ->maxLength(2048)
                        ->placeholder('https://24logist.ru/community/auth/vk/callback')
                        ->helperText('Должен полностью совпадать с адресом, зарегистрированным в VK ID.'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Авторизация через MAX')
                ->description('Вход работает через Mini App: MAX проверяет пользователя, затем сайт выдаёт одноразовую ссылку возврата. Webhook нужен для кнопки «Авторизоваться» в сообщении бота.')
                ->schema([
                    TextInput::make('community_max_bot_username')
                        ->label('Имя бота')
                        ->maxLength(500)
                        ->placeholder('community_bot')
                        ->helperText('Без символа @. Используется в ссылке https://max.ru/<имя_бота>?startapp=<одноразовый_код>.'),
                    Placeholder::make('community_max_mini_app_url')
                        ->label('URL мини-приложения')
                        ->content(route('community.auth.max.mini-app')),
                    Placeholder::make('community_max_bot_token_status')
                        ->label('Bot Token')
                        ->content(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_max_bot_token') ? '***' : 'Не настроен'),
                    TextInput::make('community_max_bot_token')
                        ->label('Новый Bot Token')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->dehydrated()
                        ->placeholder(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_max_bot_token') ? '***' : '')
                        ->helperText('Оставьте пустым, чтобы сохранить действующее значение.'),
                    Placeholder::make('community_max_webhook_secret_status')
                        ->label('Webhook Secret')
                        ->content(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_max_webhook_secret') ? '***' : 'Не настроен'),
                    TextInput::make('community_max_webhook_secret')
                        ->label('Новый Webhook Secret')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->dehydrated()
                        ->placeholder(fn (?SiteSetting $record): string => $record?->hasCommunitySecret('community_max_webhook_secret') ? '***' : '')
                        ->helperText('MAX передаёт значение в заголовке X-Max-Bot-Api-Secret. Допустимы 5–256 символов: латиница, цифры, _ и -. Оставьте пустым, чтобы сохранить действующее значение.'),
                    Placeholder::make('community_max_webhook_url')
                        ->label('Webhook URL')
                        ->content(route('community.webhooks.max')),
                    Placeholder::make('community_max_webhook_events')
                        ->label('События webhook')
                        ->content('bot_started, bot_stopped, dialog_removed'),
                    Actions::make([
                        Action::make('registerMaxWebhook')
                            ->label('Зарегистрировать webhook в MAX')
                            ->icon('heroicon-o-link')
                            ->color('primary')
                            ->requiresConfirmation()
                            ->modalHeading('Подключить webhook MAX?')
                            ->modalDescription('Сайт сохранит текущие настройки формы и затем подпишет бота на необходимые события MAX.')
                            ->action(fn (EditGeneralSiteSetting $livewire) => $livewire->saveAndRegisterMaxWebhook()),
                    ])->columnSpanFull(),
                ])
                ->columns(2)
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
    private static function epdPopupTab(): array
    {
        return [
            Section::make('Всплывающие баннеры')
                ->description('Каждый слайдер отдельно включает или выключает свой баннер.')
                ->schema([
                    Toggle::make('epd_popup_enabled')
                        ->label('Всплывающий баннер ЭПД «Приглашение на презентацию»')
                        ->helperText('Включает или выключает баннер с формой записи на презентацию.')
                        ->default(true),
                    Toggle::make('epd_popup_registration_enabled')
                        ->label('Показывать баннер «Создать личный кабинет»')
                        ->helperText('Включает или выключает баннер: 14 дней тестового доступа, встроенный ЭДО/ЭПД и бесплатная настройка.')
                        ->default(false),
                    Section::make('Баннер «Создать личный кабинет»')
                        ->description('Изображение, плашка поверх него и весь текст правой части всплывающего баннера.')
                        ->schema([
                            FileUpload::make('epd_popup_registration_image_path')
                                ->label('Изображение баннера')
                                ->disk('public')
                                ->directory('site/banners')
                                ->visibility('public')
                                ->image()
                                ->imagePreviewHeight('240')
                                ->maxFiles(1)
                                ->maxSize(10240)
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                                ->fetchFileInformation(false)
                                ->openable()
                                ->downloadable()
                                ->getUploadedFileUsing(self::uploadPreview(...))
                                ->helperText('Рекомендуемый размер — 1254×1254 px. PNG, JPG или WebP до 10 МБ. Если поле пустое, используется стандартная картинка.')
                                ->columnSpanFull(),
                            TextInput::make('epd_popup_registration_image_alt')
                                ->label('Описание изображения')
                                ->maxLength(255)
                                ->helperText('Текст для доступности и поисковых систем.')
                                ->columnSpanFull(),
                            TextInput::make('epd_popup_registration_badge_value')
                                ->label('Крупный текст плашки')
                                ->placeholder('−50%')
                                ->maxLength(30)
                                ->helperText('Например: −50%, Подарок или Бесплатно.'),
                            Select::make('epd_popup_registration_badge_value_font')
                                ->label('Шрифт крупного текста')
                                ->options([
                                    'geologica' => 'Geologica — фирменный',
                                    'arial_black' => 'Arial Black — массивный',
                                    'arial' => 'Arial — нейтральный',
                                    'verdana' => 'Verdana — широкий',
                                    'trebuchet' => 'Trebuchet MS — мягкий',
                                    'georgia' => 'Georgia — с засечками',
                                ])
                                ->default('geologica')
                                ->native(false)
                                ->required()
                                ->helperText('Применяется только к крупному значению слева.'),
                            TextInput::make('epd_popup_registration_badge_label')
                                ->label('Подпись плашки')
                                ->placeholder('на пакет ЭПД')
                                ->maxLength(100)
                                ->helperText('Текст справа от крупного значения.'),
                            TextInput::make('epd_popup_registration_eyebrow')
                                ->label('Надзаголовок')
                                ->maxLength(100),
                            TextInput::make('epd_popup_registration_title')
                                ->label('Заголовок')
                                ->maxLength(255),
                            Textarea::make('epd_popup_registration_description')
                                ->label('Описание')
                                ->rows(3)
                                ->maxLength(1000)
                                ->columnSpanFull(),
                            TextInput::make('epd_popup_registration_benefit_1')
                                ->label('Преимущество 1')
                                ->maxLength(255),
                            TextInput::make('epd_popup_registration_benefit_2')
                                ->label('Преимущество 2')
                                ->maxLength(255),
                            TextInput::make('epd_popup_registration_benefit_3')
                                ->label('Преимущество 3')
                                ->maxLength(255),
                            TextInput::make('epd_popup_registration_button_text')
                                ->label('Текст кнопки')
                                ->maxLength(100),
                            TextInput::make('epd_popup_registration_button_url')
                                ->label('Ссылка кнопки')
                                ->url()
                                ->maxLength(2048)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
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
                    Actions::make([
                        Action::make('refreshLlmsTxt')
                            ->label('Обновить llms.txt')
                            ->icon('heroicon-o-arrow-path')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalHeading('Обновить llms.txt?')
                            ->modalDescription('Текущее содержимое поля llms.txt будет заменено списком опубликованных страниц, статей и используемых тегов.')
                            ->action(function (LlmsTxtService $llms, $set): void {
                                $result = $llms->refreshFromPublishedContent();
                                $set('llms_txt_extra', $result['content']);

                                Notification::make()
                                    ->title('llms.txt обновлён')
                                    ->body("Добавлено: страниц — {$result['pages']}, статей — {$result['posts']}, тегов — {$result['tags']}.")
                                    ->success()
                                    ->send();
                            }),
                    ])
                        ->alignEnd()
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
                                return '*** — пароль сохранён';
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
                            ? '***'
                            : 'Пароль почтового ящика')
                        ->helperText('Если видите ***, пароль уже сохранён. Оставьте поле пустым, чтобы не менять его.')
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
