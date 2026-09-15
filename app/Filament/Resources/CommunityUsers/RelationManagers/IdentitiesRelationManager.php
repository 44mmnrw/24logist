<?php

namespace App\Filament\Resources\CommunityUsers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IdentitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'identities';

    protected static ?string $title = 'Привязанные соцсети и способы входа';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')->label('Сервис')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'telegram' => 'Telegram',
                    'max' => 'MAX',
                    'vk' => 'VK ID',
                    default => $state,
                }),
                TextColumn::make('provider_user_id')->label('ID в сервисе')->copyable()->searchable(),
                IconColumn::make('bot_access')->label('Доступ бота')->boolean(),
                IconColumn::make('notifications_enabled')->label('Уведомления')->boolean(),
                TextColumn::make('bot_status')->label('Статус бота')->badge(),
                TextColumn::make('last_verified_at')->label('Последний вход/проверка')->dateTime('d.m.Y H:i:s')->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label('Привязан')->dateTime('d.m.Y H:i:s')->sortable(),
            ])
            ->defaultSort('last_verified_at', 'desc');
    }
}
