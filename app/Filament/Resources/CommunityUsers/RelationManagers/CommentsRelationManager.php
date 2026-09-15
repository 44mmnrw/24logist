<?php

namespace App\Filament\Resources\CommunityUsers\RelationManagers;

use App\Models\CommunityComment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Комментарии участника';

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
                TextColumn::make('post.title')->label('Тема')->limit(65)->wrap(),
                TextColumn::make('body_markdown')
                    ->label('Комментарий')
                    ->limit(100)
                    ->wrap()
                    ->tooltip(fn (CommunityComment $record): ?string => $record->body_markdown),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('score')->label('Рейтинг')->sortable(),
                TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i:s')->sortable(),
                TextColumn::make('edited_at')->label('Изменён')->dateTime('d.m.Y H:i:s')->placeholder('—'),
                TextColumn::make('deleted_at')->label('Удалён')->dateTime('d.m.Y H:i:s')->placeholder('—')->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
