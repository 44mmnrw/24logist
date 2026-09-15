<?php

namespace App\Filament\Resources\CommunitySettings;

use App\Filament\Resources\CommunitySettings\Pages\EditCommunitySetting;
use App\Filament\Resources\CommunitySettings\Schemas\CommunitySettingForm;
use App\Models\SiteSetting;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

use function Filament\Support\original_request;

class CommunitySettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Настройки сообщества';

    protected static ?string $modelLabel = 'настройки сообщества';

    protected static ?string $pluralModelLabel = 'Настройки сообщества';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    protected static ?string $slug = 'community-settings';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(CommunitySettingForm::components());
    }

    public static function getPages(): array
    {
        return [
            'edit' => EditCommunitySetting::route('/'),
        ];
    }

    public static function getNavigationUrl(): string
    {
        return static::getUrl('edit');
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
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
                ->group(static::getNavigationGroup())
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
