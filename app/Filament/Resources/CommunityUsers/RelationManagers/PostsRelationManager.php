<?php

namespace App\Filament\Resources\CommunityUsers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    protected static ?string $title = 'Темы участника';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withTrashed())
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('title')->label('Тема')->searchable()->wrap()->limit(90),
                TextColumn::make('category.name')->label('Рубрика')->placeholder('—'),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('score')->label('Рейтинг')->sortable(),
                TextColumn::make('comments_count')->label('Комментариев')->sortable(),
                TextColumn::make('published_at')->label('Опубликована')->dateTime('d.m.Y H:i:s')->placeholder('—')->sortable(),
                TextColumn::make('deleted_at')->label('Удалена')->dateTime('d.m.Y H:i:s')->placeholder('—')->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
