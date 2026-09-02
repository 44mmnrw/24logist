<?php

namespace App\Filament\Resources\CommunityPosts;

use App\Filament\Resources\CommunityPosts\Pages\EditCommunityPost;
use App\Filament\Resources\CommunityPosts\Pages\ListCommunityPosts;
use App\Models\CommunityPost;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class CommunityPostResource extends Resource
{
    protected static ?string $model = CommunityPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Темы';

    protected static ?string $modelLabel = 'тема';

    protected static ?string $pluralModelLabel = 'Темы сообщества';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Заголовок')->required()->maxLength(180)->columnSpanFull(),
            Select::make('community_category_id')->relationship('category', 'name')->label('Рубрика')->required(),
            Select::make('status')->label('Статус')->options(['published' => 'Опубликована', 'hidden' => 'Скрыта', 'deleted' => 'Удалена'])->required(),
            Toggle::make('is_pinned')->label('Закреплена'),
            DateTimePicker::make('locked_at')->label('Закрыта с'),
            TextInput::make('score')->label('Рейтинг')->numeric()->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->label('Тема')->searchable()->limit(70),
            TextColumn::make('author.username')->label('Автор')->placeholder('[удалён]'),
            TextColumn::make('category.name')->label('Рубрика'),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('score')->label('Рейтинг')->sortable(),
            TextColumn::make('comments_count')->label('Комментарии')->sortable(),
            IconColumn::make('is_pinned')->label('Закреплена')->boolean(),
            TextColumn::make('published_at')->label('Опубликована')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([SelectFilter::make('status')->options(['published' => 'Опубликована', 'hidden' => 'Скрыта', 'deleted' => 'Удалена'])])->defaultSort('published_at', 'desc')->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunityPosts::route('/'), 'edit' => EditCommunityPost::route('/{record}/edit')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
