<?php

namespace App\Filament\Resources\EtrnRouletteJackpots;

use App\Filament\Resources\EtrnRouletteJackpots\Pages\ListEtrnRouletteJackpots;
use App\Filament\Resources\EtrnRouletteSpins\EtrnRouletteSpinResource;
use App\Models\EtrnRouletteSpin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EtrnRouletteJackpotResource extends Resource
{
    protected static ?string $model = EtrnRouletteSpin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = '3 логистРу';

    protected static ?string $modelLabel = 'супербонус рулетки';

    protected static ?string $pluralModelLabel = 'Выпадения 3 логистРу';

    protected static string|\UnitEnum|null $navigationGroup = 'Игры';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_jackpot', true)
            ->with('communityUser');
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Дата и время')->dateTime('d.m.Y H:i:s')->sortable(),
            TextColumn::make('communityUser.display_name')->label('Участник')->placeholder('Гость'),
            TextColumn::make('communityUser.username')->label('ID профиля')->placeholder('—')->copyable(),
            TextColumn::make('prize_participation')->label('Участвует за приз')
                ->state(fn (EtrnRouletteSpin $record): string => $record->etrn_roulette_player_id === null ? 'Нет' : 'Да'),
            TextColumn::make('ip_address')->label('IP')->placeholder('Не записан')->copyable(),
            TextColumn::make('id')->label('ID вращения')->copyable(),
        ])->defaultSort('created_at', 'desc')
            ->recordUrl(fn (EtrnRouletteSpin $record): string => EtrnRouletteSpinResource::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return ['index' => ListEtrnRouletteJackpots::route('/')];
    }
}
