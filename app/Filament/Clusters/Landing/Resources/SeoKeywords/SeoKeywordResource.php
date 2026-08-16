<?php

namespace App\Filament\Clusters\Landing\Resources\SeoKeywords;

use App\Filament\Clusters\Landing\Resources\SeoKeywords\Pages\CreateSeoKeyword;
use App\Filament\Clusters\Landing\Resources\SeoKeywords\Pages\EditSeoKeyword;
use App\Filament\Clusters\Landing\Resources\SeoKeywords\Pages\ListSeoKeywords;
use App\Filament\Clusters\Seo\SeoCluster;
use App\Models\SeoKeyword;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SeoKeywordResource extends Resource
{
    protected static ?string $model = SeoKeyword::class;

    protected static ?string $cluster = SeoCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static ?string $navigationLabel = 'Запросы';

    protected static ?string $modelLabel = 'поисковый запрос';

    protected static ?string $pluralModelLabel = 'Поисковые запросы';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('phrase')->label('Запрос')->required()->maxLength(500)->columnSpanFull(),
            Select::make('seo_keyword_cluster_id')
                ->label('Кластер')
                ->relationship('cluster', 'name')
                ->searchable()
                ->preload()
                ->native(false),
            TextInput::make('target_url')->label('Целевая URL')->url()->maxLength(500),
            Select::make('region_id')->label('Регион')->options(['225' => 'Россия (225)'])->default('225')->native(false),
            Select::make('device')
                ->label('Устройство')
                ->options([
                    'DEVICE_ALL' => 'Все устройства',
                    'DEVICE_DESKTOP' => 'Компьютеры',
                    'DEVICE_PHONE' => 'Телефоны',
                    'DEVICE_TABLET' => 'Планшеты',
                ])
                ->default('DEVICE_ALL')
                ->native(false),
            Toggle::make('is_active')->label('Отслеживать')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('phrase')
                    ->label('Запрос')
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->lineClamp(2)
                    ->width('42%')
                    ->tooltip(fn (SeoKeyword $record): string => $record->phrase),
                TextColumn::make('cluster.name')->label('Кластер')->badge()->searchable()->sortable(),
                TextColumn::make('source_type')
                    ->label('Тип Wordstat')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('latest_wordstat_count')
                    ->label('Wordstat')
                    ->numeric()
                    ->sortable()
                    ->tooltip(fn (SeoKeyword $record): ?string => $record->wordstat_updated_at
                        ? 'Обновлён: '.$record->wordstat_updated_at->format('d.m.Y H:i')
                        : null),
                TextColumn::make('latest_position')
                    ->label('Позиция')
                    ->placeholder('> 100')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 10 => 'success',
                        $state <= 30 => 'warning',
                        default => 'danger',
                    })
                    ->tooltip(fn (SeoKeyword $record): ?string => $record->position_checked_at
                        ? 'Проверена: '.$record->position_checked_at->format('d.m.Y H:i')
                        : null)
                    ->sortable(),
                TextColumn::make('latest_result_url')
                    ->label('URL в выдаче')
                    ->limit(38)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('wordstat_updated_at')
                    ->label('Wordstat обновлён')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('position_checked_at')
                    ->label('Позиция проверена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label('Активен')->boolean()->alignCenter(),
            ])
            ->defaultSort('latest_wordstat_count', 'desc')
            ->filters([
                SelectFilter::make('seo_keyword_cluster_id')->label('Кластер')->relationship('cluster', 'name')->searchable()->preload(),
                SelectFilter::make('is_active')->label('Отслеживание')->options(['1' => 'Активные', '0' => 'Отключённые'])->default('1'),
                SelectFilter::make('device')->label('Устройство')->options([
                    'DEVICE_ALL' => 'Все устройства',
                    'DEVICE_DESKTOP' => 'Компьютеры',
                    'DEVICE_PHONE' => 'Телефоны',
                    'DEVICE_TABLET' => 'Планшеты',
                ]),
                SelectFilter::make('source_type')->label('Тип Wordstat')->options([
                    'result' => 'Целевой результат',
                    'association' => 'Ассоциация',
                    'result,association' => 'Результат и ассоциация',
                ]),
            ])
            ->recordActions([EditAction::make()->iconButton()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeoKeywords::route('/'),
            'create' => CreateSeoKeyword::route('/create'),
            'edit' => EditSeoKeyword::route('/{record}/edit'),
        ];
    }
}
