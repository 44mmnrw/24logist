<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections;

use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\ListLandingSections;
use App\Filament\Clusters\Landing\Resources\LandingSections\RelationManagers\BlocksRelationManager;
use App\Models\LandingSection;
use App\Support\LandingIcons;
use App\Support\LandingMedia;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LandingSectionResource extends Resource
{
    protected static ?string $model = LandingSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Секции';

    protected static ?string $modelLabel = 'секция';

    protected static ?string $pluralModelLabel = 'Секции лендинга';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Название в админке')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('Ключ секции')
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('kicker')
                    ->label('Надзаголовок')
                    ->maxLength(255),
                TextInput::make('title')
                    ->label('Заголовок')
                    ->maxLength(255),
                Textarea::make('subtitle')
                    ->label('Подзаголовок')
                    ->rows(2),
                Textarea::make('description')
                    ->label(fn (?LandingSection $record): string => match ($record?->slug) {
                        'footer' => 'Текст под логотипом',
                        default => 'Описание',
                    })
                    ->rows(4),
                TextInput::make('badge_text')
                    ->label('Текст бейджа')
                    ->maxLength(255),
                TextInput::make('badge_icon')
                    ->label('Иконка бейджа')
                    ->helperText('Ключ из спрайта, например icon:badge-star')
                    ->datalist(array_map(fn ($key) => LandingIcons::toStorage($key), array_keys(LandingIcons::OPTIONS))),
                TextInput::make('button_primary_text')
                    ->label('Текст основной кнопки')
                    ->maxLength(255),
                TextInput::make('button_primary_url')
                    ->label('Ссылка основной кнопки')
                    ->maxLength(255),
                TextInput::make('button_secondary_text')
                    ->label('Текст второй кнопки')
                    ->maxLength(255),
                TextInput::make('button_secondary_url')
                    ->label('Ссылка второй кнопки')
                    ->maxLength(255),
                TextInput::make('extra.carousel_delay_ms')
                    ->label('Задержка карусели (сек)')
                    ->numeric()
                    ->minValue(2)
                    ->maxValue(60)
                    ->default(5)
                    ->suffix('сек')
                    ->helperText('Пауза между слайдами при автопрокрутке (2–60 сек).')
                    ->formatStateUsing(fn ($state): int => (int) round(((int) ($state ?? 5000)) / 1000) ?: 5)
                    ->dehydrateStateUsing(fn ($state): int => max(2000, ((int) ($state ?: 5)) * 1000))
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'hero'),
                Repeater::make('hero_carousel_slides')
                    ->label('Слайды баннера')
                    ->dehydrated(true)
                    ->extraAttributes(['class' => 'hero-carousel-repeater'], true)
                    ->columns(2)
                    ->schema([
                        static::heroCarouselImageUpload(),
                        TextInput::make('alt')
                            ->label('Alt-текст')
                            ->maxLength(255)
                            ->placeholder('Интерфейс ЛогистРу')
                            ->columnSpan(1)
                            ->extraAttributes(['class' => 'hero-carousel-alt'], true),
                    ])
                    ->minItems(1)
                    ->maxItems(12)
                    ->reorderable()
                    ->collapsible()
                    ->itemLabel(fn (array $state): string => filled($state['alt'] ?? null)
                        ? (string) $state['alt']
                        : 'Слайд')
                    ->addActionLabel('Добавить слайд')
                    ->columnSpanFull()
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'hero')
                    ->helperText('Загрузите одно или несколько изображений. Дождитесь превью, затем «Сохранить». PNG, JPG или WebP до 8 МБ.'),
                static::publicImageUpload(
                    name: 'mobile_image',
                    label: 'Изображение в рамке телефона',
                    directory: 'landing/mobile',
                    visibleSlug: 'mobile',
                    helperText: 'Скриншот экрана внутри рамки телефона. Дождитесь превью, затем нажмите «Сохранить». PNG, JPG или WebP до 8 МБ.',
                ),
                TextInput::make('extra.mobile_image_alt')
                    ->label('Alt-текст скриншота')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'mobile'),
                TextInput::make('extra.pill_left_text')
                    ->label('Текст левой плашки')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'mobile'),
                Select::make('extra.pill_left_icon')
                    ->label('Иконка левой плашки')
                    ->options(LandingIcons::OPTIONS)
                    ->searchable()
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? LandingIcons::toStorage($state) : null)
                    ->formatStateUsing(fn (?string $state) => LandingIcons::resolve($state))
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'mobile'),
                TextInput::make('extra.pill_right_text')
                    ->label('Текст правой плашки')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'mobile'),
                Select::make('extra.pill_right_icon')
                    ->label('Иконка правой плашки')
                    ->options(LandingIcons::OPTIONS)
                    ->searchable()
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? LandingIcons::toStorage($state) : null)
                    ->formatStateUsing(fn (?string $state) => LandingIcons::resolve($state))
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'mobile'),
                TextInput::make('extra.finish_title')
                    ->label('Заголовок финального шага')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                Textarea::make('extra.finish_description')
                    ->label('Текст финального шага')
                    ->rows(2)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                TextInput::make('extra.success_title')
                    ->label('Заголовок после отправки')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                Textarea::make('extra.success_description')
                    ->label('Текст после отправки')
                    ->rows(2)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                TextInput::make('extra.submit_button_text')
                    ->label('Текст кнопки отправки')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                Select::make('extra.next_button_icon')
                    ->label('Иконка кнопки «Далее»')
                    ->options(LandingIcons::OPTIONS)
                    ->searchable()
                    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? LandingIcons::toStorage($state) : null)
                    ->formatStateUsing(fn (?string $state) => LandingIcons::resolve($state))
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                TextInput::make('extra.copyright')
                    ->label('Копирайт')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'footer'),
                TextInput::make('extra.tagline')
                    ->label('Слоган в нижней строке')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'footer'),
                KeyValue::make('extra')
                    ->label('Дополнительные поля')
                    ->keyLabel('Ключ')
                    ->valueLabel('Значение')
                    ->reorderable()
                    ->visible(fn (?LandingSection $record): bool => ! in_array($record?->slug, ['hero', 'mobile', 'quiz', 'footer'], true)),
                Toggle::make('is_active')
                    ->label('Активна')
                    ->default(true),
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
                TextColumn::make('name')
                    ->label('Секция')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Ключ')
                    ->badge(),
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->limit(40),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                TextColumn::make('blocks_count')
                    ->label('Блоков')
                    ->counts('allBlocks'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [
            BlocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingSections::route('/'),
            'edit' => EditLandingSection::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withCount('allBlocks');
    }

    protected static function heroCarouselImageUpload(): FileUpload
    {
        return FileUpload::make('image')
            ->label('Изображение')
            ->disk('public')
            ->directory('landing/hero')
            ->visibility('public')
            ->image()
            ->imagePreviewHeight('112')
            ->panelLayout('compact')
            ->maxFiles(1)
            ->required()
            ->maxSize(8192)
            ->orientImagesFromExif(false)
            ->openable()
            ->downloadable()
            ->columnSpan(1)
            ->extraAttributes(['class' => 'hero-carousel-upload'], true)
            ->formatStateUsing(function (mixed $state): array {
                $path = LandingMedia::normalizePath($state);

                return $path ? [$path] : [];
            })
            ->dehydrateStateUsing(fn (mixed $state): mixed => $state)
            ->getUploadedFileUsing(static::relativeUploadUrl(...));
    }

    protected static function publicImageUpload(
        string $name,
        string $label,
        string $directory,
        string $visibleSlug,
        ?string $helperText = null,
    ): FileUpload {
        return FileUpload::make($name)
            ->label($label)
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->image()
            ->imagePreviewHeight('200')
            ->maxFiles(1)
            ->maxSize(8192)
            ->orientImagesFromExif(false)
            ->openable()
            ->downloadable()
            ->getUploadedFileUsing(static::relativeUploadUrl(...))
            ->visible(fn (?LandingSection $record): bool => $record?->slug === $visibleSlug)
            ->helperText($helperText);
    }

    protected static function relativeUploadUrl(FileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        $info = $component->getUploadedFile($file, $storedFileNames);

        if ($info === null || ! isset($info['url'])) {
            return $info;
        }

        $path = parse_url($info['url'], PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            $info['url'] = $path;
        }

        foreach (['openableUrl', 'downloadableUrl'] as $key) {
            if (! isset($info[$key])) {
                continue;
            }

            $urlPath = parse_url($info[$key], PHP_URL_PATH);

            if (is_string($urlPath) && $urlPath !== '') {
                $info[$key] = $urlPath;
            }
        }

        return $info;
    }
}
