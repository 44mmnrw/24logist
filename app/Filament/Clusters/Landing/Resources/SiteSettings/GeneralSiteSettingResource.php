<?php

namespace App\Filament\Clusters\Landing\Resources\SiteSettings;

use App\Filament\Clusters\Landing\Resources\SiteSettings\Pages\EditGeneralSiteSetting;
use App\Models\SiteSetting;
use App\Support\FilamentUploadPreview;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
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
                    ->helperText('Иконка вкладки браузера. SVG, PNG, ICO или WebP до 1 МБ. Пусто — используется images/logo.svg.')
                    ->columnSpanFull(),
                TextInput::make('og_title')
                    ->label('Open Graph — заголовок (главная)')
                    ->maxLength(255)
                    ->placeholder('ЛогистРу — платформа для логистики')
                    ->helperText('Для главной и превью в соцсетях. Пусто — заголовок секции Hero.')
                    ->columnSpanFull(),
                Textarea::make('og_description')
                    ->label('Open Graph — описание (главная)')
                    ->rows(3)
                    ->maxLength(500)
                    ->helperText('Пусто — подзаголовок или описание секции Hero.')
                    ->columnSpanFull(),
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
                    ->helperText('Рекомендуется 1200×630 px, PNG или JPG до 4 МБ. Пусто — logo.svg.')
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
