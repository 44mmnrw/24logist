<?php

namespace App\Filament\Clusters\Landing\Resources\SeoKeywordSnapshots;

use App\Filament\Clusters\Landing\Resources\SeoKeywordSnapshots\Pages\ListSeoKeywordSnapshots;
use App\Filament\Clusters\Seo\SeoCluster;
use App\Models\SeoKeywordSnapshot;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeoKeywordSnapshotResource extends Resource
{
    protected static ?string $model = SeoKeywordSnapshot::class;

    protected static ?string $cluster = SeoCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'История метрик';

    protected static ?string $modelLabel = 'срез метрик';

    protected static ?string $pluralModelLabel = 'История SEO-метрик';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('recorded_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('keyword.phrase')->label('Запрос')->searchable()->limit(65)->wrap(),
                TextColumn::make('keyword.cluster.name')->label('Кластер')->badge(),
                TextColumn::make('source')->label('Источник')->badge(),
                TextColumn::make('wordstat_count')->label('Wordstat')->numeric()->sortable(),
                TextColumn::make('position')->label('Позиция')->placeholder('> 100')->badge()->sortable(),
                TextColumn::make('result_url')->label('URL в выдаче')->limit(45)->copyable()->toggleable(),
                TextColumn::make('seo_research_run_id')->label('Запуск')->prefix('#')->sortable(),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->filters([
                SelectFilter::make('source')->label('Источник')->options([
                    'yandex_wordstat' => 'Yandex Wordstat',
                    'yandex_search' => 'Yandex Search',
                ]),
                SelectFilter::make('seo_research_run_id')->label('Запуск')->relationship('run', 'id')->searchable(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSeoKeywordSnapshots::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
