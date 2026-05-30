<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections\RelationManagers;

use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Services\LandingPageService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class HeaderButtonsRelationManager extends RelationManager
{
    protected static string $relationship = 'blocks';

    protected static ?string $title = 'Кнопки в шапке';

    protected static ?string $modelLabel = 'кнопка';

    protected static ?string $pluralModelLabel = 'Кнопки';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof LandingSection && $ownerRecord->slug === 'header';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Название')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('link')
                    ->label('Ссылка')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('#quiz или /admin/login')
                    ->columnSpanFull(),
                Select::make('button_style')
                    ->label('Стиль')
                    ->options([
                        'link' => 'Текстовая ссылка',
                        'primary' => 'Primary-кнопка',
                    ])
                    ->default('link')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Активна')
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
            ->modifyQueryUsing(fn ($query) => $query->where('block_type', 'header_button'))
            ->columns([
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('link')
                    ->label('Ссылка')
                    ->limit(40),
                TextColumn::make('button_style')
                    ->label('Стиль')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'primary' => 'Primary-кнопка',
                        default => 'Текстовая ссылка',
                    }),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить кнопку')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['section_slug'] = $this->getOwnerRecord()->slug;
                        $data['block_type'] = 'header_button';

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
