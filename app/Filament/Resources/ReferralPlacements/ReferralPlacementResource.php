<?php

namespace App\Filament\Resources\ReferralPlacements;

use App\Filament\Resources\ReferralPlacements\Pages\CreateReferralPlacement;
use App\Filament\Resources\ReferralPlacements\Pages\EditReferralPlacement;
use App\Filament\Resources\ReferralPlacements\Pages\ListReferralPlacements;
use App\Models\ReferralPlacement;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralPlacementResource extends Resource
{
    protected static ?string $model = ReferralPlacement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Публичные размещения';

    protected static ?string $modelLabel = 'публичное размещение';

    protected static ?string $pluralModelLabel = 'Публичные размещения';

    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная программа';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('participant_id')->label('Участник')->relationship('participant', 'company_name')->required()->searchable()->preload(),
            Select::make('status')->label('Статус')->options(['draft' => 'Заявка', 'active' => 'Активно', 'rejected' => 'Отклонено', 'ended' => 'Завершено'])->required()->default('draft')
                ->helperText('Активировать можно только после ввода ERID.'),
            TextInput::make('platform')->label('Площадка')->required()->maxLength(255),
            TextInput::make('placement_url')->label('URL размещения')->required()->url()->maxLength(2048)->columnSpanFull(),
            Textarea::make('creative_text')->label('Материал и обязательная маркировка')->required()->columnSpanFull(),
            TextInput::make('erid')->label('ERID')->required(fn ($get) => $get('status') === 'active')->maxLength(255),
            TextInput::make('ord_name')->label('ОРД')->maxLength(255),
            TextInput::make('contract_number')->label('Договор')->maxLength(255),
            TextInput::make('act_number')->label('Акт')->maxLength(255),
            TextInput::make('placement_cost_minor')->label('Стоимость, ₽')->numeric()->minValue(0)
                ->formatStateUsing(fn ($state) => $state === null ? null : ((int) $state / 100))
                ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) round(((float) $state) * 100) : null),
            DateTimePicker::make('starts_at')->label('Начало'),
            DateTimePicker::make('ends_at')->label('Окончание')->after('starts_at'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('participant.company_name')->label('Участник')->searchable(),
            TextColumn::make('platform')->label('Площадка'),
            TextColumn::make('code')->label('Код')->copyable(),
            TextColumn::make('erid')->label('ERID')->copyable(),
            TextColumn::make('status')->label('Статус')->badge(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReferralPlacements::route('/'), 'create' => CreateReferralPlacement::route('/create'), 'edit' => EditReferralPlacement::route('/{record}/edit')];
    }
}
