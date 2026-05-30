<?php

namespace App\Filament\Clusters\Landing\Resources\LandingLeads;

use App\Filament\Clusters\Landing\Resources\LandingLeads\Pages\ListLandingLeads;
use App\Filament\Clusters\Landing\Resources\LandingLeads\Pages\ViewLandingLead;
use App\Models\LandingLead;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LandingLeadResource extends Resource
{
    protected static ?string $model = LandingLead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $navigationLabel = 'Заявки';

    protected static ?string $modelLabel = 'заявка';

    protected static ?string $pluralModelLabel = 'Заявки с сайта';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label('Статус')
                    ->options(LandingLead::STATUS_LABELS)
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('type')
                    ->label('Тип')
                    ->formatStateUsing(fn (LandingLead $record): string => $record->typeLabel()),
                TextEntry::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (LandingLead $record): string => $record->statusLabel()),
                TextEntry::make('name')->label('Имя'),
                TextEntry::make('phone')->label('Телефон'),
                TextEntry::make('email')->label('Email')->placeholder('—'),
                TextEntry::make('message')
                    ->label('Сообщение')
                    ->placeholder('—')
                    ->columnSpanFull()
                    ->visible(fn (LandingLead $record): bool => $record->type === LandingLead::TYPE_CONTACT),
                TextEntry::make('recommended_plan_title')
                    ->label('Рекомендованный тариф')
                    ->placeholder('—')
                    ->visible(fn (LandingLead $record): bool => $record->type === LandingLead::TYPE_QUIZ),
                RepeatableEntry::make('quiz_answers')
                    ->label('Ответы квиза')
                    ->schema([
                        TextEntry::make('question')->label('Вопрос'),
                        TextEntry::make('answer')->label('Ответ'),
                    ])
                    ->columnSpanFull()
                    ->visible(fn (LandingLead $record): bool => $record->type === LandingLead::TYPE_QUIZ),
                TextEntry::make('source_url')->label('Страница')->placeholder('—')->columnSpanFull(),
                TextEntry::make('ip')->label('IP')->placeholder('—'),
                TextEntry::make('created_at')->label('Создана')->dateTime('d.m.Y H:i'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LandingLead::TYPE_LABELS[$state] ?? $state),
                TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('recommended_plan_title')
                    ->label('Тариф')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LandingLead::STATUS_LABELS[$state] ?? $state),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(LandingLead::TYPE_LABELS),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(LandingLead::STATUS_LABELS),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingLeads::route('/'),
            'view' => ViewLandingLead::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
