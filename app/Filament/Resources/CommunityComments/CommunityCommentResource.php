<?php

namespace App\Filament\Resources\CommunityComments;

use App\Filament\Resources\CommunityComments\Pages\EditCommunityComment;
use App\Filament\Resources\CommunityComments\Pages\ListCommunityComments;
use App\Models\CommunityComment;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunityCommentResource extends Resource
{
    protected static ?string $model = CommunityComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Комментарии';

    protected static ?string $modelLabel = 'комментарий';

    protected static ?string $pluralModelLabel = 'Комментарии сообщества';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body_markdown')->label('Текст')->rows(8)->maxLength(5000)->disabled()->columnSpanFull(),
            Select::make('status')->label('Статус')->options(['published' => 'Опубликован', 'hidden' => 'Скрыт', 'deleted' => 'Удалён'])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('body_markdown')->label('Комментарий')->limit(90)->searchable(),
            TextColumn::make('author.username')->label('Автор')->placeholder('[удалён]'),
            TextColumn::make('post.title')->label('Тема')->limit(50),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('score')->label('Рейтинг')->sortable(),
            TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([SelectFilter::make('status')->options(['published' => 'Опубликован', 'hidden' => 'Скрыт', 'deleted' => 'Удалён'])])->defaultSort('created_at', 'desc')->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunityComments::route('/'), 'edit' => EditCommunityComment::route('/{record}/edit')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
