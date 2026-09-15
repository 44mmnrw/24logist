<?php

namespace App\Filament\Resources\CommunityUsers\RelationManagers;

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
                TextColumn::make('action')
                    ->label('Действие')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'warn' => 'Предупреждение',
                        'suspend' => 'Временная блокировка',
                        'ban' => 'Бессрочная блокировка',
                        'unrestrict' => 'Снятие ограничений',
                        'delete_user' => 'Удаление аккаунта',
                        'restore_user' => 'Восстановление аккаунта',
                        'admin_edit' => 'Редактирование профиля',
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
