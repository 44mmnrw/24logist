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
use App\Support\RichContent\FontSizeRichContentPlugin;
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
                        ->helperText('Статья откроется по адресу /blog/slug. При изменении старый адрес автоматически получит 301-редирект.'),
                    Textarea::make('subtitle')
                        ->label('Подзаголовок')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Выводится непосредственно под заголовком H1 на странице статьи.')
                        ->columnSpanFull(),
                    Textarea::make('excerpt')
                        ->label('Краткое описание')
                        ->rows(3)
                        ->maxLength(700)
                        ->helperText('Используется в карточках блога и как запасное meta description. Под заголовком статьи не выводится.')
                        ->columnSpanFull(),
                    RichEditor::make('body')
                        ->label('Текст статьи')
                        ->required()
                        ->plugins([FontSizeRichContentPlugin::make()])
                        ->helperText('Газетная вёрстка: «Вводный текст» — первый вводный абзац статьи; H2 — раздел с линией; H3 — синий подзаголовок; «Цитата» — акцентная врезка; «Выделение» — предупреждение; «Таблица» — блок с цифрами; скрепка — изображение внутри статьи. Заголовок статьи уже выводится как H1, поэтому внутри текста обычно используйте H2–H6.')
                        ->toolbarButtons([
                            ['undo', 'redo'],
                            [
                                ToolbarButtonGroup::make('Стиль', ['paragraph', 'lead', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'small'])
                                    ->textualButtons(),
                            ],
                            [
                                ToolbarButtonGroup::make('Размер', ['fontSizeDefault', 'fontSize12', 'fontSize14', 'fontSize16', 'fontSize18', 'fontSize20', 'fontSize24', 'fontSize28', 'fontSize32'])
                                    ->textualButtons(),
                            ],
                            ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript'],
                            ['link', 'textColor', 'highlight', 'code', 'clearFormatting'],
                            ['bulletList', 'orderedList', 'blockquote', 'codeBlock', 'details'],
                            [
                                ToolbarButtonGroup::make('Таблица', [
                                    'table',
                                    'tableAddColumnBefore',
                                    'tableAddColumnAfter',
                                    'tableDeleteColumn',
                                    'tableAddRowBefore',
                                    'tableAddRowAfter',
                                    'tableDeleteRow',
                                    'tableMergeCells',
                                    'tableSplitCell',
                                    'tableToggleHeaderRow',
                                    'tableToggleHeaderCell',
                                    'tableDelete',
                                ])->textualButtons(),
                            ],
                            ['grid', 'gridDelete', 'attachFiles', 'horizontalRule'],
                            ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
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
                ->description('Используется на странице статьи и как резервное изображение, если отдельная миниатюра не задана.')
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
            Section::make('Миниатюра карточки')
                ->description('При сохранении статьи миниатюра 16:9 автоматически создаётся из обложки. При необходимости здесь можно загрузить отдельный вариант.')
                ->schema([
                    FileUpload::make('card_source_image_path')
                        ->label('Исходник карточки 16:9')
                        ->disk('public')
                        ->directory('blog/cards')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatioOptions(['16:9'])
                        ->imageAspectRatio('16:9')
                        ->automaticallyCropImagesToAspectRatio()
                        ->automaticallyResizeImagesMode('cover')
                        ->automaticallyResizeImagesToWidth('1200')
                        ->automaticallyResizeImagesToHeight('675')
                        ->imagePreviewHeight('220')
                        ->maxFiles(1)
                        ->maxSize(8192)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileUsing(static::uploadPreview(...))
                        ->helperText('Необязательно. Если оставить поле пустым, карточка будет создана из обложки. Логотип физически встраивается в скачиваемый файл.')
                        ->columnSpanFull(),
                    Toggle::make('show_card_logo')
                        ->label('Показывать логотип ЛогистРу на изображениях статьи')
                        ->helperText('Логотип автоматически появится на карточке и обложке внутри статьи. Отключите, если он уже встроен в изображение.')
                        ->default(true)
                        ->inline(false)
                        ->columnSpanFull(),
                    Select::make('card_logo_position')
                        ->label('Расположение логотипа')
                        ->options(BlogPost::LOGO_POSITIONS)
                        ->default('top-left')
                        ->required()
                        ->native(false)
                        ->selectablePlaceholder(false)
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
                    ->limit(44)
                    ->wrap(),
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
                    ->date('d.m.Y')
                    ->sortable(),
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
                EditAction::make()->iconButton(),
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
