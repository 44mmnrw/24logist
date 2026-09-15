<?php

namespace App\Filament\Resources\CommunityPosts\RelationManagers;

use App\Models\CommunityReport;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    protected static ?string $title = 'Жалобы на тему';

    protected static ?string $modelLabel = 'жалоба';

    protected static ?string $pluralModelLabel = 'Жалобы';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Получена')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('reporter.username')->label('От кого')->placeholder('[удалён]'),
                TextColumn::make('reason')
                    ->label('Причина')
                    ->formatStateUsing(fn (CommunityReport $record): string => $record->reasonLabel())
                    ->badge(),
                TextColumn::make('details')->label('Комментарий')->wrap()->placeholder('—'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Открыта',
                        'actioned' => 'Приняты меры',
                        'dismissed' => 'Отклонена',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'danger',
                        'actioned' => 'warning',
                        'dismissed' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('resolved_at')->label('Рассмотрена')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
