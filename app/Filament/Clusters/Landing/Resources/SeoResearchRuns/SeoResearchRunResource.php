<?php

namespace App\Filament\Clusters\Landing\Resources\SeoResearchRuns;

use App\Filament\Clusters\Landing\Resources\SeoResearchRuns\Pages\ListSeoResearchRuns;
use App\Models\SeoResearchRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeoResearchRunResource extends Resource
{
    protected static ?string $model = SeoResearchRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'SEO: обновления';

    protected static ?string $modelLabel = 'запуск обновления';

    protected static ?string $pluralModelLabel = 'История обновлений SEO';

    protected static ?int $navigationSort = 23;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->prefix('#')->sortable(),
                TextColumn::make('started_at')->label('Начат')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('type')->label('Тип')->badge(),
                TextColumn::make('source')->label('Источник')->badge(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'running', 'pending' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('progress')
                    ->label('Обработано')
                    ->state(fn (SeoResearchRun $record): string => $record->processed_items.' / '.$record->total_items),
                TextColumn::make('region_id')->label('Регион'),
                TextColumn::make('device')->label('Устройство')->toggleable(),
                TextColumn::make('finished_at')->label('Завершён')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('error')->label('Ошибка')->limit(80)->tooltip(fn (SeoResearchRun $record): ?string => $record->error)->toggleable(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('type')->label('Тип')->options(['wordstat' => 'Wordstat', 'positions' => 'Позиции']),
                SelectFilter::make('status')->label('Статус')->options([
                    'completed' => 'Завершён',
                    'completed_with_errors' => 'Завершён с ошибками',
                    'running' => 'Выполняется',
                    'failed' => 'Ошибка',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSeoResearchRuns::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
