<?php

namespace App\Filament\Resources\ReferralAttributions;

use App\Filament\Resources\ReferralAttributions\Pages\ListReferralAttributions;
use App\Models\ReferralAttribution;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralAttributionResource extends Resource
{
    protected static ?string $model = ReferralAttribution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Приглашённые компании';

    protected static ?string $modelLabel = 'приглашённая компания';

    protected static ?string $pluralModelLabel = 'Приглашённые компании';

    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная программа';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('participant.company_name')->label('Рекомендатель')->searchable(),
            TextColumn::make('referred_company_name')->label('Приглашённая компания')->searchable(),
            TextColumn::make('referred_inn')->label('ИНН')->searchable(),
            TextColumn::make('source_type')->label('Источник')->badge(),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('commission_bps')->label('Комиссия')->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', ' ').' %'),
            TextColumn::make('attributed_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
        ])->defaultSort('attributed_at', 'desc')->recordActions([
            Action::make('approve')->label('Подтвердить')->visible(fn (ReferralAttribution $record) => in_array($record->status, ['pending', 'rejected'], true))
                ->requiresConfirmation()->action(fn (ReferralAttribution $record) => $record->update(['status' => 'confirmed', 'rejection_reason' => null, 'confirmed_at' => now()])),
            Action::make('reject')->label('Отклонить')->color('danger')->visible(fn (ReferralAttribution $record) => $record->status !== 'rejected')
                ->schema([Textarea::make('reason')->label('Причина')->required()])
                ->action(fn (ReferralAttribution $record, array $data) => $record->update(['status' => 'rejected', 'rejection_reason' => $data['reason']])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReferralAttributions::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
