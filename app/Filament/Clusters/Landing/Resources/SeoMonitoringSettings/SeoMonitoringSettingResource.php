<?php

namespace App\Filament\Clusters\Landing\Resources\SeoMonitoringSettings;

use App\Filament\Clusters\Landing\Resources\SeoMonitoringSettings\Pages\EditSeoMonitoringSetting;
use App\Filament\Clusters\Seo\SeoCluster;
use App\Models\SeoMonitoringSetting;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

use function Filament\Support\original_request;

class SeoMonitoringSettingResource extends Resource
{
    protected static ?string $model = SeoMonitoringSetting::class;

    protected static ?string $cluster = SeoCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Настройки API';

    protected static ?string $modelLabel = 'настройки SEO API';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('yandex_api_key')
                ->label('API-ключ Yandex Search API')
                ->password()->revealable()->autocomplete(false)
                ->helperText(fn (): string => SeoMonitoringSetting::instance()->hasYandexApiKey()
                    ? 'Ключ настроен. Оставьте поле пустым, чтобы сохранить текущий.'
                    : 'Укажите API-ключ сервисного аккаунта Yandex Cloud.'),
            TextInput::make('yandex_folder_id')->label('Folder ID Yandex Cloud')->maxLength(255),
            TextInput::make('target_host')->label('Домен для проверки позиций')->required()->maxLength(255),
            Select::make('default_region_id')->label('Регион')->options(['225' => 'Россия (225)'])->required()->native(false),
            Select::make('default_device')->label('Устройство')->options([
                'DEVICE_ALL' => 'Все устройства',
                'DEVICE_DESKTOP' => 'Компьютеры',
                'DEVICE_PHONE' => 'Телефоны',
                'DEVICE_TABLET' => 'Планшеты',
            ])->required()->native(false),
            TextInput::make('position_depth')->label('Глубина проверки позиций')->numeric()->minValue(1)->maxValue(100)->required(),
            TextInput::make('position_batch_limit')->label('Запросов за одну проверку')->numeric()->minValue(1)->maxValue(50)->required(),
            TextInput::make('wordstat_limit')->label('Фраз Wordstat на кластер')->numeric()->minValue(1)->maxValue(2000)->required(),
        ])->columns(2);
    }

    public static function getPages(): array
    {
        return ['edit' => EditSeoMonitoringSetting::route('/')];
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit');
    }

    /** @param array<mixed> $parameters */
    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return static::getUrl('edit', $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }

    public static function getNavigationItems(): array
    {
        if (! static::shouldRegisterNavigation() || ! static::canAccess()) {
            return [];
        }

        return [NavigationItem::make(static::getNavigationLabel())
            ->icon(static::getNavigationIcon())
            ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteBaseName().'.*'))
            ->sort(static::getNavigationSort())
            ->url(static::getNavigationUrl())];
    }

    public static function canViewAny(): bool
    {
        return static::canEdit(SeoMonitoringSetting::instance());
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
