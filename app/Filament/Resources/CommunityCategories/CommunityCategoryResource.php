<?php

namespace App\Filament\Resources\CommunityCategories;

use App\Filament\Resources\CommunityCategories\Pages\CreateCommunityCategory;
use App\Filament\Resources\CommunityCategories\Pages\EditCommunityCategory;
use App\Filament\Resources\CommunityCategories\Pages\ListCommunityCategories;
use App\Models\CommunityCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommunityCategoryResource extends Resource
{
    protected static ?string $model = CommunityCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Рубрики сообщества';

    protected static ?string $modelLabel = 'рубрика сообщества';

    protected static ?string $pluralModelLabel = 'Рубрики сообщества';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Название')->required()->maxLength(100),
            TextInput::make('slug')->label('Slug')->required()->maxLength(120)->unique(ignoreRecord: true),
            Textarea::make('description')->label('Описание')->maxLength(500)->columnSpanFull(),
            TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
            Toggle::make('is_active')->label('Активна')->default(true),
            Toggle::make('posting_enabled')->label('Разрешены публикации')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Название')->searchable()->sortable(),
            TextColumn::make('slug')->label('Slug')->badge(),
            TextColumn::make('posts_count')->label('Тем')->counts('posts'),
            IconColumn::make('is_active')->label('Активна')->boolean(),
            IconColumn::make('posting_enabled')->label('Публикации')->boolean(),
            TextColumn::make('sort_order')->label('Порядок')->sortable(),
        ])->defaultSort('sort_order')->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunityCategories::route('/'), 'create' => CreateCommunityCategory::route('/create'), 'edit' => EditCommunityCategory::route('/{record}/edit')];
    }
}
