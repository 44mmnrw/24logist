<?php

namespace App\Filament\Resources\ReferralPartnerApplications;

use App\Filament\Resources\ReferralPartnerApplications\Pages\ListReferralPartnerApplications;
use App\Models\ReferralParticipant;
use App\Models\ReferralPartnerApplication;
use App\Models\ReferralTerm;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ReferralPartnerApplicationResource extends Resource
{
    protected static ?string $model = ReferralPartnerApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static ?string $navigationLabel = 'Заявки партнёров';

    protected static ?string $modelLabel = 'заявка партнёра';

    protected static ?string $pluralModelLabel = 'Заявки партнёров';

    protected static string|\UnitEnum|null $navigationGroup = 'Реферальная программа';

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('company_name')->label('Компания')->searchable()->sortable(),
            TextColumn::make('inn')->label('ИНН')->searchable(),
            TextColumn::make('contact_name')->label('Контакт'),
            TextColumn::make('contact_email')->label('Email')->copyable(),
            TextColumn::make('contact_phone')->label('Телефон')->copyable(),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('created_at')->label('Подана')->dateTime('d.m.Y H:i')->sortable(),
        ])->defaultSort('created_at', 'desc')->recordActions([
            Action::make('approve')
                ->label('Одобрить')
                ->visible(fn (ReferralPartnerApplication $record): bool => $record->status === 'pending')
                ->schema([
                    TextInput::make('external_account_id')->label('ID аккаунта основной платформы')->required()->maxLength(191)->unique(ReferralParticipant::class, 'external_account_id'),
                    Select::make('terms_id')->label('Версия условий')->required()->options(fn (): array => ReferralTerm::query()->where('status', 'active')->pluck('name', 'id')->all()),
                ])
                ->action(function (ReferralPartnerApplication $record, array $data): void {
                    DB::transaction(function () use ($record, $data): void {
                        $participant = ReferralParticipant::query()->firstOrCreate(
                            ['inn' => $record->inn],
                            [
                                'terms_id' => $data['terms_id'],
                                'external_account_id' => $data['external_account_id'],
                                'company_name' => $record->company_name,
                                'contact_email' => $record->contact_email,
                                'status' => 'pending',
                            ],
                        );
                        $record->update([
                            'status' => 'approved',
                            'participant_id' => $participant->id,
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_at' => now(),
                            'review_note' => null,
                        ]);
                    });
                    Notification::make()->title('Партнёр создан')->body('Отправьте компании подписанную ссылку на кабинет из раздела «Партнёры».')->success()->send();
                }),
            Action::make('reject')
                ->label('Отклонить')
                ->color('danger')
                ->visible(fn (ReferralPartnerApplication $record): bool => $record->status === 'pending')
                ->schema([Textarea::make('review_note')->label('Причина')->required()])
                ->action(fn (ReferralPartnerApplication $record, array $data) => $record->update([
                    'status' => 'rejected',
                    'review_note' => $data['review_note'],
                    'reviewed_by_user_id' => auth()->id(),
                    'reviewed_at' => now(),
                ])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListReferralPartnerApplications::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
