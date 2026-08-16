<?php

namespace App\Filament\Clusters\Landing\Resources\BlogPosts;

use App\Filament\Clusters\Landing\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Clusters\Landing\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Clusters\Landing\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Support\FilamentUploadPreview;
use App\Support\OpenGraph;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\TextColor;
use Filament\Forms\Components\RichEditor\ToolbarButtonGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Блог';

    protected static ?string $modelLabel = 'статья';

    protected static ?string $pluralModelLabel = 'Статьи блога';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('blog_post_tabs')
                    ->tabs([
                        Tab::make('content')
                            ->label('Контент')
                            ->icon('heroicon-o-document-text')
                            ->schema(self::contentTab()),
                        Tab::make('media')
                            ->label('Изображения')
                            ->icon('heroicon-o-photo')
                            ->schema(self::mediaTab()),
                        Tab::make('seo')
                            ->label('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema(self::seoTab()),
                        Tab::make('publishing')
                            ->label('Публикация')
                            ->icon('heroicon-o-calendar-days')
                            ->schema(self::publishingTab()),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function contentTab(): array
    {
        return [
            Section::make('Основное')
                ->schema([
                    TextInput::make('title')
                        ->label('Заголовок')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                            if (filled($get('slug'))) {
                                return;
                            }

                            $set('slug', Str::slug((string) $state));
                        }),
                    TextInput::make('slug')
                        ->label('URL-адрес')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Статья откроется по адресу /blog/slug. Используйте латиницу, цифры и дефисы.'),
                    Textarea::make('subtitle')
                        ->label('Подзаголовок')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Textarea::make('excerpt')
                        ->label('Краткое описание')
                        ->rows(3)
                        ->maxLength(700)
                        ->helperText('Показывается в карточках и используется как fallback для meta description.')
                        ->columnSpanFull(),
                    RichEditor::make('body')
                        ->label('Текст статьи')
                        ->required()
                        ->helperText('Статейная верстка: «Лид» — вводный абзац, H2 — раздел с линией, H3 — синий подзаголовок, «Цитата» — акцентная врезка, выделение — предупреждение, таблица — блок с цифрами, скрепка — изображение.')
                        ->toolbarButtons([
                            ['undo', 'redo'],
                            [
                                ToolbarButtonGroup::make('Стиль', ['paragraph', 'lead', 'h2', 'h3'])
                                    ->textualButtons(),
                            ],
                            ['bold', 'italic', 'underline', 'link', 'textColor', 'highlight'],
                            ['bulletList', 'orderedList', 'blockquote'],
                            ['table', 'attachFiles', 'horizontalRule'],
                            ['alignStart', 'alignCenter', 'alignEnd'],
                        ])
                        ->textColors([
                            'navy' => TextColor::make('Тёмно-синий', '#22384d', '#b9c9d8'),
                            'blue' => TextColor::make('Синий', '#2878bd', '#7db7e8'),
                            'teal' => TextColor::make('Бирюзовый', '#268b8d', '#65c7c9'),
                            'red' => TextColor::make('Красный акцент', '#b23a3a', '#ef8d8d'),
                            'muted' => TextColor::make('Серый', '#697786', '#aab5c0'),
                        ])
                        ->fileAttachments(true)
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('blog/body')
                        ->fileAttachmentsVisibility('public')
                        ->fileAttachmentsMaxSize(8192)
                        ->resizableImages()
                        ->extraAttributes(['class' => 'blog-article-editor'])
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Классификация')
                ->schema([
                    Select::make('blog_category_id')
                        ->label('Рубрика')
                        ->relationship(
                            name: 'blogCategory',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->orderBy('sort_order')
                                ->orderBy('name'),
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->helperText('Список рубрик создается отдельно в разделе «Рубрики блога».'),
                    Select::make('tags')
                        ->options(fn (): array => BlogTag::query()->orderBy('name')->pluck('name', 'name')->all())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->label('Теги')
                        ->placeholder('Выберите теги')
                        ->helperText('В списке доступны только теги из раздела «Теги блога».')
                        ->columnSpanFull(),
                    TextInput::make('author_name')
                        ->label('Автор')
                        ->maxLength(255)
                        ->placeholder('Команда 24Logist'),
                    Select::make('author_type')
                        ->label('Тип автора')
                        ->options([
                            'Person' => 'Person',
                            'Organization' => 'Organization',
                        ])
                        ->default('Person')
                        ->native(false),
                    TextInput::make('author_url')
                        ->label('URL автора')
                        ->maxLength(500)
                        ->placeholder('https://24logist.ru/pages/about'),
                    TextInput::make('reading_time_minutes')
                        ->label('Время чтения')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(999)
                        ->suffix('мин'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function mediaTab(): array
    {
        return [
            Section::make('Обложка статьи')
                ->description('Используется на странице статьи и в карточках блога.')
                ->schema([
                    FileUpload::make('cover_image_path')
                        ->label('Изображение обложки')
                        ->disk('public')
                        ->directory('blog/covers')
                        ->visibility('public')
                        ->image()
                        ->imagePreviewHeight('220')
                        ->maxFiles(1)
                        ->maxSize(8192)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(static::uploadPreview(...))
                        ->helperText('Рекомендуется 1600x900 или 1200x675 px. PNG, JPG или WebP до 8 МБ.')
                        ->columnSpanFull(),
                    TextInput::make('cover_image_alt')
                        ->label('Alt-текст обложки')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function seoTab(): array
    {
        return [
            Section::make('Meta-теги')
                ->schema([
                    TextInput::make('meta_title')
                        ->label('Meta title')
                        ->maxLength(70)
                        ->helperText('Тег <title>. Если пусто, используется заголовок статьи. Рекомендуется до 60-70 символов.'),
                    Textarea::make('meta_description')
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Описание в поиске и соцсетях. Рекомендуется 120-160 символов.')
                        ->columnSpanFull(),
                    Textarea::make('meta_keywords')
                        ->label('Meta keywords')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Через запятую. Необязательно.')
                        ->columnSpanFull(),
                    Select::make('meta_robots')
                        ->label('Robots')
                        ->options([
                            OpenGraph::ROBOTS_INDEX => 'index, follow (по умолчанию)',
                            'noindex, nofollow' => 'noindex, nofollow',
                            'noindex, follow' => 'noindex, follow',
                            'index, nofollow' => 'index, nofollow',
                        ])
                        ->placeholder('index, follow (по умолчанию)')
                        ->native(false),
                    TextInput::make('canonical_url')
                        ->label('Canonical URL')
                        ->maxLength(500)
                        ->placeholder('https://24logist.ru/blog/example')
                        ->helperText('Оставьте пустым, если canonical должен совпадать с URL статьи.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Open Graph и соцсети')
                ->schema([
                    TextInput::make('og_title')
                        ->label('OG title')
                        ->maxLength(255)
                        ->helperText('Пусто — используется meta title или заголовок статьи.'),
                    Select::make('og_type')
                        ->label('OG type')
                        ->options([
                            'article' => 'article',
                            'website' => 'website',
                        ])
                        ->default('article')
                        ->native(false),
                    Textarea::make('og_description')
                        ->label('OG description')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Пусто — используется meta description или краткое описание.')
                        ->columnSpanFull(),
                    FileUpload::make('og_image_path')
                        ->label('OG image')
                        ->disk('public')
                        ->directory('blog/og')
                        ->visibility('public')
                        ->image()
                        ->imagePreviewHeight('120')
                        ->maxFiles(1)
                        ->maxSize(4096)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(static::uploadPreview(...))
                        ->helperText('Рекомендуется 1200x630 px. Пусто — используется обложка статьи или OG сайта.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Twitter / X Card')
                ->description('Поля для отдельного превью в Twitter / X. Если пусто, используются Open Graph данные.')
                ->schema([
                    Select::make('twitter_card')
                        ->label('Twitter card')
                        ->options([
                            'summary_large_image' => 'summary_large_image',
                            'summary' => 'summary',
                        ])
                        ->default('summary_large_image')
                        ->native(false),
                    TextInput::make('twitter_title')
                        ->label('Twitter title')
                        ->maxLength(255)
                        ->helperText('Пусто — используется OG title или meta title.'),
                    Textarea::make('twitter_description')
                        ->label('Twitter description')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Пусто — используется OG description или meta description.')
                        ->columnSpanFull(),
                    FileUpload::make('twitter_image_path')
                        ->label('Twitter image')
                        ->disk('public')
                        ->directory('blog/twitter')
                        ->visibility('public')
                        ->image()
                        ->imagePreviewHeight('120')
                        ->maxFiles(1)
                        ->maxSize(4096)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(static::uploadPreview(...))
                        ->helperText('Пусто — используется OG image, обложка статьи или OG сайта.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Schema.org')
                ->description('JSON-LD разметка статьи для поисковых систем.')
                ->schema([
                    Select::make('schema_type')
                        ->label('Тип статьи')
                        ->options([
                            'Article' => 'Article',
                            'BlogPosting' => 'BlogPosting',
                            'NewsArticle' => 'NewsArticle',
                            'TechArticle' => 'TechArticle',
                        ])
                        ->default('Article')
                        ->native(false),
                    TextInput::make('schema_headline')
                        ->label('Schema headline')
                        ->maxLength(255)
                        ->helperText('Пусто — используется заголовок статьи.'),
                    Textarea::make('schema_description')
                        ->label('Schema description')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Пусто — используется meta/OG description.')
                        ->columnSpanFull(),
                    FileUpload::make('schema_image_path')
                        ->label('Schema image')
                        ->disk('public')
                        ->directory('blog/schema')
                        ->visibility('public')
                        ->image()
                        ->imagePreviewHeight('120')
                        ->maxFiles(1)
                        ->maxSize(4096)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(static::uploadPreview(...))
                        ->helperText('Пусто — используется OG image или обложка статьи.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function publishingTab(): array
    {
        return [
            Section::make('Статус')
                ->schema([
                    Toggle::make('is_published')
                        ->label('Опубликована')
                        ->default(false)
                        ->helperText('Неопубликованные статьи не видны на сайте и не попадают в sitemap.'),
                    Toggle::make('is_featured')
                        ->label('Выделить в блоге')
                        ->default(false)
                        ->helperText('Выделенная статья показывается крупным блоком первой на странице блога.'),
                    DateTimePicker::make('published_at')
                        ->label('Дата публикации')
                        ->seconds(false)
                        ->helperText('Если статья опубликована, но дата пустая, она заполнится автоматически при сохранении. Будущая дата скрывает статью до наступления времени.'),
                    TextInput::make('sort_order')
                        ->label('Порядок')
                        ->numeric()
                        ->default(0)
                        ->helperText('Меньшее число выводится выше при одинаковой дате.'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->limit(48),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Slug скопирован'),
                TextColumn::make('category_label')
                    ->label('Рубрика')
                    ->state(fn (BlogPost $record): string => $record->displayCategory() ?: '—')
                    ->toggleable(),
                IconColumn::make('is_published')
                    ->label('Опубликована')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->state(fn (BlogPost $record): string => '/blog/'.$record->slug)
                    ->copyable()
                    ->copyableState(fn (BlogPost $record): string => $record->getUrl())
                    ->copyMessage('Ссылка скопирована')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Обновлена')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('is_published')
                    ->label('Статус')
                    ->options([
                        '1' => 'Опубликованные',
                        '0' => 'Черновики',
                    ]),
                SelectFilter::make('blog_category_id')
                    ->label('Рубрика')
                    ->options(fn () => BlogCategory::query()
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->pluck('name', 'id')),
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

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('blogCategory');
    }

    /**
     * @param  string|array<string, string>|null  $storedFileNames
     * @return array{name: string, size: int, type: ?string, url: ?string}|null
     */
    protected static function uploadPreview(FileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        return FilamentUploadPreview::resolve($component, $file, $storedFileNames);
    }
}
