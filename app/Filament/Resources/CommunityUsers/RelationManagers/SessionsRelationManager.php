<?php

namespace App\Filament\Resources\CommunityUsers\RelationManagers;

use App\Models\CommunityUserSession;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $title = 'Сессии и история активности';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('active')->label('Активна')->state(fn (CommunityUserSession $record): bool => $record->isActive())->boolean(),
                TextColumn::make('provider')->label('Вход через')->badge()->placeholder('Текущая сессия'),
                TextColumn::make('ip_address')->label('IP')->copyable()->placeholder('—'),
                TextColumn::make('user_agent')->label('User-Agent')->limit(80)->wrap()->tooltip(fn (CommunityUserSession $record): ?string => $record->user_agent),
                TextColumn::make('logged_in_at')->label('Вход')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('last_seen_at')->label('Активность')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('expires_at')->label('Истекает')->dateTime('d.m.Y H:i:s')->placeholder('—'),
                TextColumn::make('logged_out_at')->label('Выход')->dateTime('d.m.Y H:i:s')->placeholder('—'),
            ])
            ->defaultSort('last_seen_at', 'desc');
    }
}
