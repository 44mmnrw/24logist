<?php

namespace App\Filament\Clusters\Landing\Resources\BlogTags;

use App\Filament\Clusters\Landing\Resources\BlogTags\Pages\CreateBlogTag;
use App\Filament\Clusters\Landing\Resources\BlogTags\Pages\EditBlogTag;
use App\Filament\Clusters\Landing\Resources\BlogTags\Pages\ListBlogTags;
use App\Models\BlogTag;
use App\Support\FilamentUploadPreview;
use App\Support\OpenGraph;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogTagResource extends Resource
{
    protected static ?string $model = BlogTag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Теги блога';

    protected static ?string $modelLabel = 'тег блога';

    protected static ?string $pluralModelLabel = 'Теги блога';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('blog_tag_tabs')
                ->tabs([
                    Tab::make('content')
                        ->label('Основное')
                        ->icon('heroicon-o-tag')
                        ->schema(self::contentTab()),
                    Tab::make('seo')
                        ->label('SEO')
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema(self::seoTab()),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ]);
    }

    /** @return array<int, mixed> */
    private static function contentTab(): array
    {
        return [
            Section::make('Тег и постоянная ссылка')
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                            if (! filled($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Постоянная ссылка')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Адрес страницы: /tag/slug. После публикации не меняйте slug без необходимости.'),
                    Textarea::make('description')
                        ->label('Описание страницы')
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('Показывается под заголовком и используется как запасное SEO-описание.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    private static function seoTab(): array
    {
        return [
            Section::make('Meta-теги')
                ->schema([
                    TextInput::make('meta_title')->label('Meta title')->maxLength(70),
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
                    Textarea::make('meta_description')
                        ->label('Meta description')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Рекомендуемая длина — 120–160 символов.')
                        ->columnSpanFull(),
                    Textarea::make('meta_keywords')->label('Meta keywords')->rows(2)->maxLength(500)->columnSpanFull(),
                    TextInput::make('canonical_url')
                        ->label('Canonical URL')
                        ->maxLength(500)
                        ->placeholder('https://24logist.ru/tag/example')
                        ->helperText('Оставьте пустым, чтобы использовать постоянную ссылку тега.')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Open Graph и соцсети')
                ->schema([
                    TextInput::make('og_title')->label('OG title')->maxLength(255),
                    Select::make('og_type')->label('OG type')->options(['website' => 'website', 'article' => 'article'])->default('website')->native(false),
                    Textarea::make('og_description')->label('OG description')->rows(2)->maxLength(500)->columnSpanFull(),
                    self::imageUpload('og_image_path', 'OG image', 'blog/tags/og'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Twitter / X Card')
                ->schema([
                    Select::make('twitter_card')->label('Twitter card')->options(['summary_large_image' => 'summary_large_image', 'summary' => 'summary'])->default('summary_large_image')->native(false),
                    TextInput::make('twitter_title')->label('Twitter title')->maxLength(255),
                    Textarea::make('twitter_description')->label('Twitter description')->rows(2)->maxLength(500)->columnSpanFull(),
                    self::imageUpload('twitter_image_path', 'Twitter image', 'blog/tags/twitter'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Schema.org')
                ->schema([
                    Select::make('schema_type')->label('Тип страницы')->options(['CollectionPage' => 'CollectionPage', 'WebPage' => 'WebPage'])->default('CollectionPage')->native(false),
                    TextInput::make('schema_headline')->label('Schema headline')->maxLength(255),
                    Textarea::make('schema_description')->label('Schema description')->rows(2)->maxLength(500)->columnSpanFull(),
                    self::imageUpload('schema_image_path', 'Schema image', 'blog/tags/schema'),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];
    }

    private static function imageUpload(string $name, string $label, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->disk('public')
            ->directory($directory)
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
            ->helperText('PNG, JPG или WebP. Для превью рекомендуется 1200×630 px.')
            ->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Тег')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->badge()->copyable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->state(fn (BlogTag $record): string => '/tag/'.$record->slug)
                    ->copyable()
                    ->copyableState(fn (BlogTag $record): string => $record->getUrl()),
                TextColumn::make('updated_at')->label('Обновлён')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogTags::route('/'),
            'create' => CreateBlogTag::route('/create'),
            'edit' => EditBlogTag::route('/{record}/edit'),
        ];
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
