<?php

namespace App\Filament\Clusters\Landing\Resources\SiteSettings;

use App\Filament\Clusters\Landing\Resources\SiteSettings\Pages\EditGeneralSiteSetting;
use App\Models\SiteSetting;
use App\Support\FilamentUploadPreview;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\original_request;

class GeneralSiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Настройки сайта';

    protected static ?string $modelLabel = 'настройки сайта';

    protected static ?string $pluralModelLabel = 'Настройки сайта';

    protected static ?string $slug = 'site-settings';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('section_icons')
                    ->label('Иконки сайта')
                    ->content('Favicon, Apple Touch Icon и PWA-иконки.')
                    ->columnSpanFull(),
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

                Placeholder::make('section_seo')
                    ->label('SEO — главная страница')
                    ->content('Title, description и Open Graph для главной. Также используются как значения по умолчанию для CMS-страниц.')
                    ->columnSpanFull(),
                TextInput::make('seo_meta_title')
                    ->label('Meta title (главная)')
                    ->maxLength(70)
                    ->placeholder('ЛогистРу — платформа для логистики')
                    ->helperText('Тег <title> и заголовок в поиске. Рекомендуется до 60–70 символов. Пусто — Open Graph title или Hero.')
                    ->columnSpanFull(),
                Textarea::make('seo_keywords')
                    ->label('Meta keywords (общие)')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Через запятую. Необязательно — используется как fallback для страниц без своих keywords.')
                    ->columnSpanFull(),
                TextInput::make('og_title')
                    ->label('Open Graph — заголовок (главная)')
                    ->maxLength(255)
                    ->placeholder('ЛогистРу — платформа для логистики')
                    ->helperText('Пусто — заголовок секции Hero.')
                    ->columnSpanFull(),
                Textarea::make('og_description')
                    ->label('Open Graph — описание (главная)')
                    ->rows(3)
                    ->maxLength(500)
                    ->helperText('Meta description и og:description. Пусто — подзаголовок Hero.')
                    ->columnSpanFull(),
                FileUpload::make('og_image_path')
                    ->label('Open Graph — изображение (по умолчанию)')
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
                    ->helperText('1200×630 px. Используется на главной и как fallback для CMS-страниц.')
                    ->columnSpanFull(),

                Placeholder::make('section_org')
                    ->label('Организация (Schema.org)')
                    ->content('Данные для JSON-LD Organization, контактов и AI-поиска.')
                    ->columnSpanFull(),
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
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('org_phone')
                    ->label('Телефон')
                    ->tel()
                    ->maxLength(255),
                FileUpload::make('org_logo_path')
                    ->label('Логотип организации (Schema.org)')
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
                    ->helperText('Квадратный или горизонтальный логотип. Пусто — OG-изображение.')
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
                    ->label('Соцсети и профили (sameAs)')
                    ->rows(3)
                    ->helperText('По одному URL на строку: VK, Telegram, LinkedIn и т.д.')
                    ->columnSpanFull(),

                Placeholder::make('section_social')
                    ->label('Twitter / X')
                    ->content('Карточки при шаринге в Twitter/X.')
                    ->columnSpanFull(),
                TextInput::make('twitter_site')
                    ->label('Twitter site (@username)')
                    ->maxLength(255)
                    ->placeholder('@logistru'),
                TextInput::make('twitter_creator')
                    ->label('Twitter creator (@username)')
                    ->maxLength(255),

                Placeholder::make('section_verify')
                    ->label('Верификация поисковиков')
                    ->content('Коды из Google Search Console и Яндекс.Вебмастер.')
                    ->columnSpanFull(),
                TextInput::make('google_site_verification')
                    ->label('Google site verification')
                    ->maxLength(255),
                TextInput::make('yandex_site_verification')
                    ->label('Yandex verification')
                    ->maxLength(255),

                Placeholder::make('section_ai')
                    ->label('AI-поиск и LLMs.txt')
                    ->content('Краткое описание для AI-ассистентов и файл /llms.txt.')
                    ->columnSpanFull(),
                Textarea::make('ai_site_summary')
                    ->label('Краткое описание для AI')
                    ->rows(4)
                    ->maxLength(1000)
                    ->helperText('Используется в meta abstract и в /llms.txt. 2–3 предложения о продукте.')
                    ->columnSpanFull(),
                Textarea::make('llms_txt_extra')
                    ->label('Дополнительный блок в llms.txt')
                    ->rows(4)
                    ->helperText('Markdown-блок, добавляется в конец /llms.txt (тарифы, API, инструкции).')
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditGeneralSiteSetting::route('/'),
        ];
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit');
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return static::getUrl('edit', $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        if (! static::shouldRegisterNavigation() || ! static::canAccess()) {
            return [];
        }

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.*'))
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl()),
        ];
    }

    public static function canViewAny(): bool
    {
        return static::canEdit(SiteSetting::instance());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
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
