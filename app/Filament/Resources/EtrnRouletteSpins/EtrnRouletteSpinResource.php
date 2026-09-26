<?php

namespace App\Filament\Resources\EtrnRouletteSpins;

use App\Filament\Resources\EtrnRouletteSpins\Pages\ListEtrnRouletteSpins;
use App\Filament\Resources\EtrnRouletteSpins\Pages\ViewEtrnRouletteSpin;
use App\Models\EtrnRouletteSpin;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EtrnRouletteSpinResource extends Resource
{
    protected static ?string $model = EtrnRouletteSpin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Рулетка роуминга';

    protected static ?string $modelLabel = 'вращение рулетки';

    protected static ?string $pluralModelLabel = 'Все вращения рулетки';

    protected static string|\UnitEnum|null $navigationGroup = 'Игры';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('communityUser');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Когда')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('communityUser.display_name')->label('Участник')->placeholder('Гость')->searchable(),
                TextColumn::make('communityUser.username')->label('Профиль')->placeholder('—')->searchable()->copyable(),
                TextColumn::make('community_user_id')->label('ID участника')->placeholder('—')
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('result_kind')->label('Результат')->badge()
                    ->formatStateUsing(fn (EtrnRouletteSpin $record): string => $record->resultLabel())
                    ->color(fn (?string $state): string => match ($state) {
                        'jackpot' => 'warning',
                        'match' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('jackpot_chance')->label('Шанс при вращении')
                    ->state(fn (EtrnRouletteSpin $record): ?string => isset($record->outcome['jackpot_chance_percent'])
                        ? number_format((float) $record->outcome['jackpot_chance_percent'], 3, ',', ' ').' %'
                        : null)
                    ->placeholder('Не записан')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reels')->label('Барабаны')
                    ->state(fn (EtrnRouletteSpin $record): string => $record->reelSummary())
                    ->limit(80)->wrap(),
                TextColumn::make('ip_address')->label('IP')->placeholder('Не записан')->searchable()->copyable(),
                TextColumn::make('prize_participation')->label('За приз')
                    ->state(fn (EtrnRouletteSpin $record): string => $record->etrn_roulette_player_id === null ? 'Нет' : 'Да'),
                TextColumn::make('actor_key')->label('Идентификатор игрока')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 12).'…')
                    ->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')->label('Браузер')->placeholder('Не записан')
                    ->limit(80)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('id')->label('ID')->sortable()->copyable(),
            ])
            ->filters([
                SelectFilter::make('result_kind')->label('Результат')->options(EtrnRouletteSpin::RESULT_LABELS),
                Filter::make('prize')->label('Только за приз')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('etrn_roulette_player_id')),
                Filter::make('guests')->label('Только гости')
                    ->query(fn (Builder $query): Builder => $query->whereNull('community_user_id')),
                Filter::make('period')->label('Период')
                    ->schema([
                        DatePicker::make('from')->label('С'),
                        DatePicker::make('until')->label('По'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Вращение')->schema([
                TextEntry::make('id')->label('ID вращения'),
                TextEntry::make('created_at')->label('Когда')->dateTime('d.m.Y H:i:s'),
                TextEntry::make('result_kind')->label('Результат')
                    ->formatStateUsing(fn (EtrnRouletteSpin $record): string => $record->resultLabel()),
                TextEntry::make('jackpot_chance')->label('Шанс при вращении')
                    ->state(fn (EtrnRouletteSpin $record): ?string => isset($record->outcome['jackpot_chance_percent'])
                        ? number_format((float) $record->outcome['jackpot_chance_percent'], 3, ',', ' ').' %'
                        : null)
                    ->placeholder('Не записан'),
                TextEntry::make('reels')->label('Барабаны')
                    ->state(fn (EtrnRouletteSpin $record): string => $record->reelSummary())
                    ->columnSpanFull(),
                TextEntry::make('destination')->label('Куда отправилась ЭТрН')
                    ->state(fn (EtrnRouletteSpin $record): ?string => $record->outcome['destination'] ?? null)
                    ->placeholder('—'),
                TextEntry::make('prize_participation')->label('Участвует за приз')
                    ->state(fn (EtrnRouletteSpin $record): string => $record->etrn_roulette_player_id === null ? 'Нет' : 'Да'),
            ])->columns(2),
            Section::make('Участник и запрос')->schema([
                TextEntry::make('communityUser.display_name')->label('Участник')->placeholder('Гость'),
                TextEntry::make('communityUser.username')->label('Профиль')->placeholder('—'),
                TextEntry::make('player.contact_email')->label('Контактный email')->placeholder('—')->copyable(),
                TextEntry::make('community_user_id')->label('ID участника')->placeholder('—'),
                TextEntry::make('ip_address')->label('IP')->placeholder('Не записан')->copyable(),
                TextEntry::make('user_agent')->label('Браузер')->placeholder('Не записан')->columnSpanFull(),
                TextEntry::make('actor_key')->label('Идентификатор игрока')->copyable()->columnSpanFull(),
                TextEntry::make('request_id')->label('ID запроса')->copyable()->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEtrnRouletteSpins::route('/'),
            'view' => ViewEtrnRouletteSpin::route('/{record}'),
        ];
    }
}
