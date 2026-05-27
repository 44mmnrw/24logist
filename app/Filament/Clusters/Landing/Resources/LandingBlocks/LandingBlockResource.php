<?php

namespace App\Filament\Clusters\Landing\Resources\LandingBlocks;

use App\Filament\Clusters\Landing\Resources\LandingBlocks\Pages\CreateLandingBlock;
use App\Filament\Clusters\Landing\Resources\LandingBlocks\Pages\EditLandingBlock;
use App\Filament\Clusters\Landing\Resources\LandingBlocks\Pages\ListLandingBlocks;
use App\Filament\Clusters\Landing\Resources\LandingBlocks\RelationManagers\ChildrenRelationManager;
use App\Filament\Clusters\Landing\Resources\LandingBlocks\RelationManagers\QuizOptionsRelationManager;
use App\Models\LandingBlock;
use App\Models\LandingSection;
use App\Support\LandingIcons;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LandingBlockResource extends Resource
{
    protected static ?string $model = LandingBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Блоки';

    protected static ?string $modelLabel = 'блок';

    protected static ?string $pluralModelLabel = 'Блоки контента';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('section_slug')
                    ->label('Секция')
                    ->options(fn () => LandingSection::query()->orderBy('sort_order')->pluck('name', 'slug'))
                    ->required()
                    ->searchable(),
                Select::make('block_type')
                    ->label('Тип блока')
                    ->options([
                        'nav_link' => 'Ссылка навигации',
                        'bullet' => 'Пункт списка',
                        'card' => 'Карточка',
                        'stat' => 'Статистика',
                        'plan' => 'Тариф',
                        'feature' => 'Пункт тарифа',
                        'faq' => 'FAQ',
                        'question' => 'Вопрос квиза',
                        'option' => 'Вариант ответа',
                        'list_item' => 'Элемент списка',
                        'pill' => 'Тег',
                        'role' => 'Роль',
                        'note' => 'Примечание',
                        'footer_column' => 'Колонка подвала',
                        'footer_link' => 'Ссылка подвала',
                        'dashboard_metric' => 'Метрика дашборда',
                        'dashboard_route' => 'Рейс дашборда',
                        'dashboard_bottom' => 'Строка дашборда',
                        'phone_card' => 'Карточка телефона',
                    ])
                    ->required()
                    ->searchable(),
                Select::make('parent_id')
                    ->label('Родительский блок')
                    ->relationship('parent', 'title')
                    ->searchable()
                    ->nullable(),
                TextInput::make('title')
                    ->label(fn (?LandingBlock $record): string => $record?->block_type === 'faq' ? 'Вопрос' : 'Заголовок')
                    ->maxLength(255),
                TextInput::make('subtitle')
                    ->label('Подзаголовок')
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(fn (?LandingBlock $record): string => $record?->block_type === 'faq' ? 'Ответ' : 'Описание')
                    ->rows(4),
                Select::make('icon')
                    ->label('Иконка')
                    ->options(LandingIcons::OPTIONS)
                    ->searchable()
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? LandingIcons::toStorage($state) : null)
                    ->formatStateUsing(fn (?string $state) => LandingIcons::resolve($state)),
                TextInput::make('price')
                    ->label('Цена')
                    ->maxLength(255),
                TextInput::make('tag')
                    ->label('Тег')
                    ->maxLength(255),
                TextInput::make('link')
                    ->label('Ссылка')
                    ->maxLength(255),
                TextInput::make('button_text')
                    ->label('Текст кнопки')
                    ->maxLength(255),
                Select::make('button_style')
                    ->label('Стиль кнопки')
                    ->options([
                        'primary' => 'Primary',
                        'ghost' => 'Ghost',
                        'ghost-dark' => 'Ghost dark',
                    ]),
                KeyValue::make('extra')
                    ->label('Дополнительные поля')
                    ->keyLabel('Ключ')
                    ->valueLabel('Значение')
                    ->reorderable(),
                Toggle::make('is_active')
                    ->label('Активен')
                    ->default(true),
                Toggle::make('is_highlighted')
                    ->label('Выделен')
                    ->default(false),
                TextInput::make('sort_order')
                    ->label('Порядок')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('section_slug')
                    ->label('Секция')
                    ->badge()
                    ->sortable(),
                TextColumn::make('block_type')
                    ->label('Тип')
                    ->badge()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('parent.title')
                    ->label('Родитель')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('section_slug')
                    ->label('Секция')
                    ->options(fn () => LandingSection::query()->orderBy('sort_order')->pluck('name', 'slug')),
                SelectFilter::make('block_type')
                    ->label('Тип блока')
                    ->options([
                        'nav_link' => 'Ссылка навигации',
                        'bullet' => 'Пункт списка',
                        'card' => 'Карточка',
                        'stat' => 'Статистика',
                        'plan' => 'Тариф',
                        'feature' => 'Пункт тарифа',
                        'faq' => 'FAQ',
                        'question' => 'Вопрос квиза',
                        'option' => 'Вариант ответа',
                        'list_item' => 'Элемент списка',
                        'pill' => 'Тег',
                        'role' => 'Роль',
                        'note' => 'Примечание',
                        'footer_column' => 'Колонка подвала',
                        'footer_link' => 'Ссылка подвала',
                        'dashboard_metric' => 'Метрика дашборда',
                        'dashboard_route' => 'Рейс дашборда',
                        'dashboard_bottom' => 'Строка дашборда',
                        'phone_card' => 'Карточка телефона',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuizOptionsRelationManager::class,
            ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingBlocks::route('/'),
            'create' => CreateLandingBlock::route('/create'),
            'edit' => EditLandingBlock::route('/{record}/edit'),
        ];
    }
}
