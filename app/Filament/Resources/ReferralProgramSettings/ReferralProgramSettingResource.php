<?php

namespace App\Filament\Resources\ReferralProgramSettings;

use App\Filament\Resources\ReferralProgramSettings\Pages\EditReferralProgramSetting;
use App\Models\ReferralProgramSetting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class ReferralProgramSettingResource extends Resource
{
    protected static ?string $model = ReferralProgramSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Настройки программы';

    protected static ?string $modelLabel = 'настройки реферальной программы';

    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная программа';

    protected static ?string $slug = 'referral-settings';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Toggle::make('is_enabled')->label('Программа включена')->helperText('Общий выключатель новых переходов и атрибуций.'),
            Toggle::make('public_placements_enabled')->label('Публичные размещения включены'),
            TextInput::make('attribution_days')->label('Срок атрибуции, дней')->required()->numeric()->minValue(1)->maxValue(365),
            TextInput::make('offer_version')->label('Версия оферты')->maxLength(100),
            TextInput::make('offer_url')->label('Ссылка на оферту')->url()->maxLength(2048)->columnSpanFull(),
        ])->columns(2);
    }

    public static function getPages(): array
    {
        return ['edit' => EditReferralProgramSetting::route('/')];
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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
