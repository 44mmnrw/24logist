<?php

namespace App\Filament\Resources\ReferralPayouts;

use App\Filament\Resources\ReferralPayouts\Pages\ListReferralPayouts;
use App\Models\ReferralPayout;
use App\Services\Referral\ReferralPayoutService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReferralPayoutResource extends Resource
{
    protected static ?string $model = ReferralPayout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $navigationLabel = 'Реестры выплат';

    protected static ?string $pluralModelLabel = 'Реестры выплат';

    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная программа';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('participant.company_name')->label('Получатель')->searchable(),
            TextColumn::make('participant.inn')->label('ИНН'),
            TextColumn::make('period_end')->label('Период')->date('m.Y'),
            TextColumn::make('amount_minor')->label('Сумма')->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', ' ').' ₽'),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('payment_reference')->label('ID платежа'),
        ])->defaultSort('period_end', 'desc')->recordActions([
            Action::make('approve')->label('Утвердить')->visible(fn (ReferralPayout $record) => $record->status === 'draft')
                ->requiresConfirmation()->action(fn (ReferralPayout $record) => $record->update([
                    'status' => 'approved', 'approved_by_user_id' => auth()->id(), 'approved_at' => now(),
                ])),
            Action::make('markPaid')->label('Отметить выплату')->visible(fn (ReferralPayout $record) => $record->status === 'approved')
                ->schema([TextInput::make('payment_reference')->label('Банковский идентификатор')->required()->maxLength(255)])
                ->action(fn (ReferralPayout $record, array $data) => app(ReferralPayoutService::class)->markPaid($record, $data['payment_reference'], auth()->id())),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReferralPayouts::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
