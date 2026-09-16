<?php

namespace App\Filament\Resources\ReferralTerms;

use App\Filament\Resources\ReferralTerms\Pages\CreateReferralTerm;
use App\Filament\Resources\ReferralTerms\Pages\EditReferralTerm;
use App\Filament\Resources\ReferralTerms\Pages\ListReferralTerms;
use App\Models\ReferralTerm;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralTermResource extends Resource
{
    protected static ?string $model = ReferralTerm::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Версии условий';

    protected static ?string $modelLabel = 'версия условий';

    protected static ?string $pluralModelLabel = 'Версии условий';

    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная программа';

    public static function form(Schema $schema): Schema
    {
        $percent = static fn (string $name, string $label): TextInput => TextInput::make($name)
            ->label($label)->required()->numeric()->minValue(0)->maxValue(100)->suffix('%')
            ->formatStateUsing(fn ($state) => $state === null ? null : ((int) $state / 100))
            ->dehydrateStateUsing(fn ($state): int => (int) round(((float) $state) * 100));

        return $schema->components([
            TextInput::make('name')->label('Название версии')->required()->maxLength(255),
            Select::make('status')->label('Статус')->required()->options(['draft' => 'Черновик', 'active' => 'Активна', 'ended' => 'Завершена'])->default('draft'),
            $percent('commission_bps', 'Комиссия рекомендателя'),
            $percent('invitee_discount_bps', 'Скидка приглашённому'),
            TextInput::make('commission_months')->label('Период начисления, месяцев')->required()->numeric()->minValue(1)->maxValue(120),
            TextInput::make('hold_days')->label('Холд, дней')->required()->numeric()->minValue(0)->maxValue(365),
            TextInput::make('minimum_payout_minor')->label('Минимальная выплата, ₽')->required()->numeric()->minValue(0)
                ->formatStateUsing(fn ($state) => $state === null ? null : ((int) $state / 100))
                ->dehydrateStateUsing(fn ($state): int => (int) round(((float) $state) * 100)),
            DateTimePicker::make('starts_at')->label('Действует с'),
            DateTimePicker::make('ends_at')->label('Действует до')->after('starts_at'),
            DateTimePicker::make('published_at')->label('Опубликована')->helperText('Для активной версии укажите дату публикации.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Версия')->searchable()->sortable(),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('commission_bps')->label('Комиссия')->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', ' ').' %'),
            TextColumn::make('invitee_discount_bps')->label('Скидка')->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', ' ').' %'),
            TextColumn::make('starts_at')->label('Начало')->dateTime('d.m.Y H:i'),
        ])->defaultSort('id', 'desc')->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReferralTerms::route('/'), 'create' => CreateReferralTerm::route('/create'), 'edit' => EditReferralTerm::route('/{record}/edit')];
    }
}
