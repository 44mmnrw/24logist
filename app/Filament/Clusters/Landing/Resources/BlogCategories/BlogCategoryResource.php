<?php

namespace App\Filament\Clusters\Landing\Resources\BlogCategories;

use App\Filament\Clusters\Landing\Resources\BlogCategories\Pages\CreateBlogCategory;
use App\Filament\Clusters\Landing\Resources\BlogCategories\Pages\EditBlogCategory;
use App\Filament\Clusters\Landing\Resources\BlogCategories\Pages\ListBlogCategories;
use App\Models\BlogCategory;
use App\Support\FilamentUploadPreview;
use App\Support\OpenGraph;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Рубрики блога';

    protected static ?string $modelLabel = 'рубрика блога';

    protected static ?string $pluralModelLabel = 'Рубрики блога';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('blog_category_tabs')
                ->tabs([
                    Tab::make('content')->label('Основное')->icon('heroicon-o-rectangle-stack')->schema(self::contentTab()),
                    Tab::make('seo')->label('SEO')->icon('heroicon-o-magnifying-glass')->schema(self::seoTab()),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ]);
    }

    /** @return array<int, mixed> */
    private static function contentTab(): array
    {
        return [
            Section::make('Рубрика и постоянная ссылка')
                ->schema([
                    TextInput::make('name')
                        ->label('Название')->required()->maxLength(255)->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                            if (! filled($get('slug'))) {
                                $set('slug', Str::slug((string) $state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Постоянная ссылка')->required()->maxLength(255)->unique(ignoreRecord: true)
                        ->helperText('Адрес страницы: /blog/category/slug.'),
                    Textarea::make('description')
                        ->label('Описание страницы')->rows(4)->maxLength(1000)
                        ->helperText('Показывается под заголовком и используется как запасное SEO-описание.')
                        ->columnSpanFull(),
                    Toggle::make('is_active')->label('Активна')->default(true),
                    TextInput::make('sort_order')->label('Порядок')->numeric()->default(0),
                ])->columns(2)->columnSpanFull(),
        ];
    }

    /** @return array<int, mixed> */
    private static function seoTab(): array
    {
        return [
            Section::make('Meta-теги')
                ->schema([
                    TextInput::make('seo_h1')
                        ->label('H1 страницы')->maxLength(255)->placeholder('Статьи рубрики «Название»')
                        ->helperText('Главный видимый заголовок страницы рубрики.')->columnSpanFull(),
                    TextInput::make('meta_title')->label('Meta title')->maxLength(70),
                    Select::make('meta_robots')
                        ->label('Robots')
                        ->options([
                            OpenGraph::ROBOTS_INDEX => 'index, follow (по умолчанию)',
                            'noindex, nofollow' => 'noindex, nofollow',
                            'noindex, follow' => 'noindex, follow',
                            'index, nofollow' => 'index, nofollow',
                        ])->placeholder('index, follow (по умолчанию)')->native(false),
                    Textarea::make('meta_description')
                        ->label('Meta description')->rows(3)->maxLength(500)
                        ->helperText('Рекомендуемая длина — 120–160 символов.')->columnSpanFull(),
                    Textarea::make('meta_keywords')->label('Meta keywords')->rows(2)->maxLength(500)->columnSpanFull(),
                    TextInput::make('canonical_url')
                        ->label('Canonical URL')->maxLength(500)
                        ->placeholder('https://24logist.ru/blog/category/example')
                        ->helperText('Оставьте пустым, чтобы использовать постоянную ссылку рубрики.')
                        ->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Open Graph и соцсети')
                ->schema([
                    TextInput::make('og_title')->label('OG title')->maxLength(255),
                    Select::make('og_type')->label('OG type')
                        ->options(['website' => 'website', 'article' => 'article'])->default('website')->native(false),
                    Textarea::make('og_description')->label('OG description')->rows(2)->maxLength(500)->columnSpanFull(),
                    self::imageUpload('og_image_path', 'OG image', 'blog/categories/og'),
                ])->columns(2)->columnSpanFull(),
            Section::make('Twitter / X Card')
                ->schema([
                    Select::make('twitter_card')->label('Twitter card')
                        ->options(['summary_large_image' => 'summary_large_image', 'summary' => 'summary'])
                        ->default('summary_large_image')->native(false),
                    TextInput::make('twitter_title')->label('Twitter title')->maxLength(255),
                    Textarea::make('twitter_description')->label('Twitter description')->rows(2)->maxLength(500)->columnSpanFull(),
                    self::imageUpload('twitter_image_path', 'Twitter image', 'blog/categories/twitter'),
                ])->columns(2)->columnSpanFull(),
            Section::make('Schema.org')
                ->schema([
                    Select::make('schema_type')->label('Тип страницы')
                        ->options(['CollectionPage' => 'CollectionPage', 'WebPage' => 'WebPage'])
                        ->default('CollectionPage')->native(false),
                    TextInput::make('schema_headline')->label('Schema headline')->maxLength(255),
                    Textarea::make('schema_description')->label('Schema description')->rows(2)->maxLength(500)->columnSpanFull(),
                    self::imageUpload('schema_image_path', 'Schema image', 'blog/categories/schema'),
                ])->columns(2)->columnSpanFull(),
        ];
    }

    private static function imageUpload(string $name, string $label, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)->disk('public')->directory($directory)->visibility('public')->image()
            ->imagePreviewHeight('120')->maxFiles(1)->maxSize(4096)
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->fetchFileInformation(false)->openable()->downloadable()
            ->getUploadedFileUsing(static::uploadPreview(...))
            ->helperText('PNG, JPG или WebP. Для превью рекомендуется 1200×630 px.')
            ->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Рубрика')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->badge()->copyable()->copyMessage('Slug скопирован'),
                IconColumn::make('is_active')->label('Активна')->boolean(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
                TextColumn::make('posts_count')->label('Статей')->counts('posts'),
                TextColumn::make('url')->label('URL')
                    ->state(fn (BlogCategory $record): string => '/blog/category/'.$record->slug)
                    ->copyable()->copyableState(fn (BlogCategory $record): string => $record->getUrl()),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogCategories::route('/'),
            'create' => CreateBlogCategory::route('/create'),
            'edit' => EditBlogCategory::route('/{record}/edit'),
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
