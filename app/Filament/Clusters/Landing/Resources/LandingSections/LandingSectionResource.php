<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections;

use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
use App\Filament\Clusters\Landing\Resources\LandingSections\Pages\ListLandingSections;
use App\Filament\Clusters\Landing\Resources\LandingSections\RelationManagers\BlocksRelationManager;
use App\Filament\Clusters\Landing\Resources\LandingSections\RelationManagers\HeaderButtonsRelationManager;
use App\Models\LandingSection;
use App\Support\FilamentUploadPreview;
use App\Support\LandingIcons;
use App\Support\LandingMedia;
use App\Support\LandingSectionAnchor;
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
use Illuminate\Database\Eloquent\Builder;

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
                TextInput::make('anchor')
                    ->label('Якорь секции')
                    ->prefix('/#')
                    ->maxLength(64)
                    ->placeholder('why')
                    ->helperText('Ссылка на секцию с главной: /#why. Укажите только якорь без # — why, pricing, quiz.')
                    ->visible(fn (?LandingSection $record): bool => LandingSectionAnchor::supports($record)),
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
                static::iconSelect('badge_icon', 'Иконка бейджа'),
                static::iconSelect(
                    'extra.hint_icon',
                    'Иконка подсказки под кнопками',
                    fn (?LandingSection $record): bool => $record?->slug === 'hero',
                ),
                static::iconSelect(
                    'extra.primary_button_icon',
                    'Иконка на кнопках',
                    fn (?LandingSection $record): bool => $record?->slug === 'hero',
                ),
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
                    ->live()
                    ->extraAttributes(['class' => 'hero-carousel-repeater'], true)
                    ->columns(2)
                    ->schema([
                        static::heroCarouselImageUpload(),
                        TextInput::make('alt')
                            ->label('Alt-текст изображения')
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
                static::publicImageUpload(
                    name: 'mobile_image',
                    label: 'Изображение в рамке телефона',
                    directory: 'landing/driver-cabinet',
                    visibleSlug: 'driver_cabinet',
                    helperText: 'Скриншот экрана личного кабинета водителя. Дождитесь превью, затем нажмите «Сохранить». PNG, JPG или WebP до 8 МБ.',
                ),
                TextInput::make('extra.mobile_image_alt')
                    ->label('Alt-текст скриншота')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => in_array($record?->slug, ['mobile', 'driver_cabinet'], true)),
                TextInput::make('mobile_pill_left_text')
                    ->label('Левая плашка — текст')
                    ->helperText('На сайте: mobile-pill mobile-pill--left')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => in_array($record?->slug, ['mobile', 'driver_cabinet'], true)),
                static::iconSelect(
                    'mobile_pill_left_icon',
                    'Левая плашка — иконка',
                    fn (?LandingSection $record): bool => in_array($record?->slug, ['mobile', 'driver_cabinet'], true),
                ),
                TextInput::make('mobile_pill_right_text')
                    ->label('Правая плашка — текст')
                    ->helperText('На сайте: mobile-pill mobile-pill--right')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => in_array($record?->slug, ['mobile', 'driver_cabinet'], true)),
                static::iconSelect(
                    'mobile_pill_right_icon',
                    'Правая плашка — иконка',
                    fn (?LandingSection $record): bool => in_array($record?->slug, ['mobile', 'driver_cabinet'], true),
                ),
                TextInput::make('extra.finish_title')
                    ->label('Заголовок финального шага')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                Textarea::make('extra.finish_description')
                    ->label('Текст финального шага')
                    ->rows(2)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                TextInput::make('extra.recommendation_title')
                    ->label('Заголовок шага с тарифом')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                Textarea::make('extra.recommendation_description')
                    ->label('Текст шага с тарифом')
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
                TextInput::make('extra.privacy_prefix')
                    ->label('Согласие — текст перед ссылкой')
                    ->maxLength(255)
                    ->default('Нажимая кнопку, вы соглашаетесь с')
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                TextInput::make('extra.privacy_link_text')
                    ->label('Согласие — текст ссылки')
                    ->maxLength(255)
                    ->default('политикой конфиденциальности')
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'quiz'),
                static::iconSelect(
                    'extra.next_button_icon',
                    'Иконка кнопки «Далее»',
                    fn (?LandingSection $record): bool => $record?->slug === 'quiz',
                ),
                TextInput::make('extra.deadline_kicker')
                    ->label('Дедлайн — надзаголовок')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'platform'),
                TextInput::make('extra.deadline_date')
                    ->label('Дедлайн — дата')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'platform'),
                static::iconSelect(
                    'extra.deadline_icon',
                    'Дедлайн — иконка',
                    fn (?LandingSection $record): bool => $record?->slug === 'platform',
                ),
                Textarea::make('extra.deadline_text')
                    ->label('Дедлайн — текст')
                    ->rows(3)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'platform'),
                TextInput::make('extra.deadline_button_text')
                    ->label('Дедлайн — кнопка')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'platform'),
                static::iconSelect(
                    'extra.toggle_icon',
                    'Иконка раскрытия вопроса',
                    fn (?LandingSection $record): bool => $record?->slug === 'faq',
                ),
                TextInput::make('extra.brand_name')
                    ->label('Название бренда')
                    ->maxLength(255)
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'header'),
                static::iconSelect(
                    'extra.logo_icon',
                    'Иконка бренда',
                    fn (?LandingSection $record): bool => in_array($record?->slug, ['header', 'footer'], true),
                ),
                TextInput::make('footer_copyright')
                    ->label('Нижняя строка — текст слева')
                    ->helperText('Блок landing-footer__bottom-shell, левый span (копирайт)')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'footer'),
                TextInput::make('footer_tagline')
                    ->label('Нижняя строка — текст справа')
                    ->helperText('Блок landing-footer__bottom-shell, правый span (слоган)')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->visible(fn (?LandingSection $record): bool => $record?->slug === 'footer'),
                KeyValue::make('extra')
                    ->label('Дополнительные поля')
                    ->keyLabel('Ключ')
                    ->valueLabel('Значение')
                    ->reorderable()
                    ->visible(fn (?LandingSection $record): bool => ! in_array($record?->slug, ['hero', 'mobile', 'driver_cabinet', 'quiz', 'footer', 'faq', 'platform', 'header'], true)),
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
                TextColumn::make('anchor')
                    ->label('Ссылка')
                    ->formatStateUsing(function (?string $state, LandingSection $record): string {
                        $anchor = LandingSectionAnchor::id($record);

                        return $anchor !== null ? '/#'.$anchor : '—';
                    }),
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
            HeaderButtonsRelationManager::class,
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('allBlocks');
    }

    protected static function heroCarouselImageUpload(): FileUpload
    {
        // Same options as mobile_image (works on prod). Do not use formatStateUsing here:
        // in a repeater it runs on TemporaryUploadedFile and wipes upload state to [].
        return static::publicImageUpload(
            name: 'image',
            label: 'Изображение',
            directory: 'landing/hero',
            visibleSlug: null,
            helperText: 'Дождитесь превью, затем нажмите «Сохранить». PNG, JPG или WebP до 8 МБ.',
        )
            ->required()
            ->columnSpan(1)
            ->extraAttributes(['class' => 'hero-carousel-upload'], true);
    }

    protected static function publicImageUpload(
        string $name,
        string $label,
        string $directory,
        ?string $visibleSlug,
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
            ->fetchFileInformation(false)
            ->orientImagesFromExif(false)
            ->openable()
            ->downloadable()
            ->getUploadedFileUsing(static::uploadPreview(...))
            ->visible(fn (?LandingSection $record): bool => $visibleSlug === null
                || $record?->slug === $visibleSlug)
            ->helperText($helperText);
    }

    /**
     * @param  string|array<string, string>|null  $storedFileNames
     * @return array{name: string, size: int, type: ?string, url: ?string}|null
     */
    protected static function uploadPreview(FileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        return FilamentUploadPreview::resolve($component, $file, $storedFileNames);
    }

    protected static function iconSelect(string $name, string $label, ?callable $visible = null): Select
    {
        $field = Select::make($name)
            ->label($label)
            ->options(LandingIcons::OPTIONS)
            ->searchable()
            ->nullable()
            ->helperText('Иконка из SVG-спрайта (public/images/icons/sprite.svg)')
            ->dehydrateStateUsing(fn (?string $state) => LandingIcons::normalize($state))
            ->formatStateUsing(fn (?string $state) => LandingIcons::resolve($state));

        if ($visible !== null) {
            $field->visible($visible);
        }

        return $field;
    }
}
