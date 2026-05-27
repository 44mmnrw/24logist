<?php

namespace App\Filament\Clusters\Landing\Resources\LandingBlocks\RelationManagers;

use App\Models\LandingBlock;
use App\Services\LandingPageService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class QuizOptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Варианты ответа';

    protected static ?string $modelLabel = 'вариант ответа';

    protected static ?string $pluralModelLabel = 'Варианты ответа';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (! $ownerRecord instanceof LandingBlock || $ownerRecord->block_type !== 'question') {
            return false;
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Текст варианта')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('block_type', 'option'))
            ->columns([
                TextColumn::make('title')
                    ->label('Вариант')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить вариант')
                    ->mutateFormDataUsing(function (array $data): array {
                        $owner = $this->getOwnerRecord();
                        $data['section_slug'] = $owner->section_slug;
                        $data['parent_id'] = $owner->id;
                        $data['block_type'] = 'option';

                        return $data;
                    })
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
                DeleteAction::make()
                    ->after(fn () => app(LandingPageService::class)->clearCache()),
            ]);
    }
}
