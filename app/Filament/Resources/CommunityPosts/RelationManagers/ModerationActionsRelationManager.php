<?php

namespace App\Filament\Resources\CommunityPosts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModerationActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'moderationActions';

    protected static ?string $title = 'История модерации';

    protected static ?string $modelLabel = 'действие';

    protected static ?string $pluralModelLabel = 'История модерации';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('admin.name')->label('Администратор')->placeholder('Системное действие'),
                TextColumn::make('communityUser.username')->label('Модератор сообщества')->placeholder('—'),
                TextColumn::make('action')
                    ->label('Действие')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approve' => 'Одобрение / восстановление',
                        'hide' => 'Скрытие',
                        'delete' => 'Удаление',
                        'lock' => 'Закрытие обсуждения',
                        'unlock' => 'Открытие обсуждения',
                        'pin' => 'Закрепление',
                        'unpin' => 'Открепление',
                        'admin_edit' => 'Редактирование',
                        default => $state,
                    }),
                TextColumn::make('reason')->label('Причина / комментарий')->wrap()->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
