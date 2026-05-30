<?php

namespace App\Filament\Clusters\Landing\Resources\SiteSettings;

use App\Filament\Clusters\Landing\Resources\SiteSettings\Pages\EditSiteSetting;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\original_request;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Аналитика';

    protected static ?string $modelLabel = 'настройки аналитики';

    protected static ?string $pluralModelLabel = 'Аналитика';

    protected static ?string $slug = 'analytics';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('yandex_metrika_enabled')
                    ->label('Включить Яндекс Метрику')
                    ->helperText('Счётчик будет добавлен на все публичные страницы сайта')
                    ->live()
                    ->columnSpanFull(),
                TextInput::make('yandex_metrika_counter_id')
                    ->label('ID счётчика')
                    ->numeric()
                    ->minLength(4)
                    ->maxLength(20)
                    ->required(fn (Get $get): bool => (bool) $get('yandex_metrika_enabled'))
                    ->visible(fn (Get $get): bool => (bool) $get('yandex_metrika_enabled'))
                    ->helperText('Числовой ID из Яндекс Метрики: Настройки → Счётчик → номер счётчика')
                    ->columnSpanFull(),
                Toggle::make('yandex_metrika_webvisor')
                    ->label('Вебвизор')
                    ->visible(fn (Get $get): bool => (bool) $get('yandex_metrika_enabled')),
                Toggle::make('yandex_metrika_clickmap')
                    ->label('Карта кликов')
                    ->visible(fn (Get $get): bool => (bool) $get('yandex_metrika_enabled')),
                Toggle::make('yandex_metrika_track_links')
                    ->label('Отслеживание ссылок')
                    ->visible(fn (Get $get): bool => (bool) $get('yandex_metrika_enabled')),
                Toggle::make('yandex_metrika_accurate_track_bounce')
                    ->label('Точный показатель отказов')
                    ->visible(fn (Get $get): bool => (bool) $get('yandex_metrika_enabled')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditSiteSetting::route('/'),
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
}
