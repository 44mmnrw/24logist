<?php

namespace App\Filament\Resources\ReferralParticipants;

use App\Filament\Resources\ReferralParticipants\Pages\CreateReferralParticipant;
use App\Filament\Resources\ReferralParticipants\Pages\EditReferralParticipant;
use App\Filament\Resources\ReferralParticipants\Pages\ListReferralParticipants;
use App\Models\ReferralParticipant;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class ReferralParticipantResource extends Resource
{
    protected static ?string $model = ReferralParticipant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Участники пилота';

    protected static ?string $modelLabel = 'участник';

    protected static ?string $pluralModelLabel = 'Участники пилота';

    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная программа';

    public static function form(Schema $schema): Schema
    {
        $percent = static fn (string $name, string $label): TextInput => TextInput::make($name)->label($label)->numeric()->minValue(0)->maxValue(100)->suffix('%')
            ->formatStateUsing(fn ($state) => $state === null ? null : ((int) $state / 100))
            ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) round(((float) $state) * 100) : null);

        return $schema->components([
            TextInput::make('external_account_id')->label('ID аккаунта платформы')->required()->maxLength(191)->unique(ignoreRecord: true),
            TextInput::make('company_name')->label('Компания')->required()->maxLength(255),
            TextInput::make('inn')->label('ИНН')->required()->regex('/^\\d{10}(\\d{2})?$/')->unique(ignoreRecord: true),
            TextInput::make('contact_email')->label('Email')->required()->email()->maxLength(255),
            Select::make('terms_id')->label('Версия условий')->relationship('terms', 'name')->searchable()->preload(),
            Select::make('status')->label('Статус')->required()->options(['pending' => 'Ожидает', 'active' => 'Активен', 'blocked' => 'Заблокирован'])->default('pending'),
            TextInput::make('code')->label('Персональный код')->maxLength(32)->unique(ignoreRecord: true)->helperText('Если оставить пустым, код создастся автоматически.'),
            $percent('commission_bps_override', 'Персональная комиссия'),
            $percent('invitee_discount_bps_override', 'Персональная скидка'),
            TextInput::make('commission_months_override')->label('Персональный период, месяцев')->numeric()->minValue(1),
            TextInput::make('hold_days_override')->label('Персональный холд, дней')->numeric()->minValue(0),
            TextInput::make('minimum_payout_minor_override')->label('Персональный порог, ₽')->numeric()->minValue(0)
                ->formatStateUsing(fn ($state) => $state === null ? null : ((int) $state / 100))
                ->dehydrateStateUsing(fn ($state) => filled($state) ? (int) round(((float) $state) * 100) : null),
            TextInput::make('offer_version')->label('Принятая версия оферты')->maxLength(100),
            DateTimePicker::make('offer_accepted_at')->label('Оферта принята'),
            DateTimePicker::make('bank_details_verified_at')->label('Реквизиты проверены'),
            KeyValue::make('bank_details')->label('Платёжные реквизиты')->keyLabel('Поле')->valueLabel('Значение')->columnSpanFull(),
            DateTimePicker::make('suspended_at')->label('Заблокирован с'),
            Textarea::make('suspension_reason')->label('Причина блокировки')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('company_name')->label('Компания')->searchable()->sortable(),
            TextColumn::make('inn')->label('ИНН')->searchable(),
            TextColumn::make('code')->label('Код')->copyable(),
            TextColumn::make('portal_url')->label('Кабинет')->state(fn (ReferralParticipant $record): string => URL::signedRoute('referrals.portal.show', ['participant' => $record->id]))->copyable()->limit(24),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('attributions_count')->label('Регистраций')->counts('attributions'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReferralParticipants::route('/'), 'create' => CreateReferralParticipant::route('/create'), 'edit' => EditReferralParticipant::route('/{record}/edit')];
    }
}
