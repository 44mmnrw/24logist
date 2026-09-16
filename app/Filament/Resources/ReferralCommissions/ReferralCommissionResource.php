<?php

namespace App\Filament\Resources\ReferralCommissions;

use App\Filament\Resources\ReferralCommissions\Pages\ListReferralCommissions;
use App\Models\ReferralCommission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralCommissionResource extends Resource
{
    protected static ?string $model = ReferralCommission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Начисления';

    protected static ?string $pluralModelLabel = 'Начисления и корректировки';

    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная программа';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('attribution.participant.company_name')->label('Рекомендатель')->searchable(),
            TextColumn::make('attribution.referred_company_name')->label('Клиент'),
            TextColumn::make('type')->label('Тип')->badge(),
            TextColumn::make('base_minor')->label('База')->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', ' ').' ₽'),
            TextColumn::make('amount_minor')->label('Сумма')->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', ' ').' ₽'),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('available_at')->label('Доступно с')->dateTime('d.m.Y H:i'),
            TextColumn::make('event_key')->label('Событие')->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListReferralCommissions::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
