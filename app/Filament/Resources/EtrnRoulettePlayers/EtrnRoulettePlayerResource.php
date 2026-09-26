<?php

namespace App\Filament\Resources\EtrnRoulettePlayers;

use App\Filament\Resources\EtrnRoulettePlayers\Pages\ListEtrnRoulettePlayers;
use App\Models\EtrnRoulettePlayer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EtrnRoulettePlayerResource extends Resource
{
    protected static ?string $model = EtrnRoulettePlayer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Участники розыгрыша';

    protected static ?string $modelLabel = 'участник розыгрыша';

    protected static ?string $pluralModelLabel = 'Участники розыгрыша';

    protected static string|\UnitEnum|null $navigationGroup = 'Игры';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('communityUser');
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('joined_at')->label('Регистрация')->dateTime('d.m.Y H:i')->sortable(),
            TextColumn::make('communityUser.display_name')->label('Участник')->placeholder('—')->searchable(),
            TextColumn::make('communityUser.username')->label('Профиль')->placeholder('—')->searchable()->copyable(),
            TextColumn::make('contact_email')->label('Контактный email')->placeholder('Не подтверждён')->searchable()->copyable(),
            TextColumn::make('contact_verified_at')->label('Email подтверждён')->dateTime('d.m.Y H:i')->placeholder('Нет')->sortable(),
            TextColumn::make('attempts')->label('Учтено попыток')->sortable(),
            TextColumn::make('contact_consent_at')->label('Согласие')->dateTime('d.m.Y H:i')->placeholder('Нет')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('contact_consent_ip')->label('IP согласия')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('joined_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListEtrnRoulettePlayers::route('/')];
    }
}
