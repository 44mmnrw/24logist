<?php

namespace App\Filament\Clusters\Landing\Resources\SeoKeywordClusters;

use App\Filament\Clusters\Landing\Resources\SeoKeywordClusters\Pages\CreateSeoKeywordCluster;
use App\Filament\Clusters\Landing\Resources\SeoKeywordClusters\Pages\EditSeoKeywordCluster;
use App\Filament\Clusters\Landing\Resources\SeoKeywordClusters\Pages\ListSeoKeywordClusters;
use App\Models\SeoKeywordCluster;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SeoKeywordClusterResource extends Resource
{
    protected static ?string $model = SeoKeywordCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'SEO: кластеры';

    protected static ?string $modelLabel = 'SEO-кластер';

    protected static ?string $pluralModelLabel = 'SEO-кластеры';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                    if (! filled($get('slug'))) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
            Select::make('search_intent')
                ->label('Интент')
                ->options([
                    'informational' => 'Информационный',
                    'commercial' => 'Коммерческий',
                    'transactional' => 'Транзакционный',
                    'navigational' => 'Навигационный',
                ])
                ->native(false),
            TextInput::make('target_url')->label('Целевая страница')->url()->maxLength(500),
            Textarea::make('description')->label('Комментарий')->rows(3)->columnSpanFull(),
            Toggle::make('is_active')->label('Активен')->default(true),
            TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Кластер')->searchable()->sortable(),
                TextColumn::make('search_intent')->label('Интент')->badge(),
                TextColumn::make('keywords_count')->label('Запросов')->counts('keywords')->sortable(),
                TextColumn::make('target_url')->label('Целевая URL')->limit(45)->copyable()->toggleable(),
                IconColumn::make('is_active')->label('Активен')->boolean(),
                TextColumn::make('updated_at')->label('Обновлён')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeoKeywordClusters::route('/'),
            'create' => CreateSeoKeywordCluster::route('/create'),
            'edit' => EditSeoKeywordCluster::route('/{record}/edit'),
        ];
    }
}
