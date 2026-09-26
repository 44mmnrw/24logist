<?php

namespace App\Filament\Resources\CommunitySeoPages;

use App\Filament\Resources\CommunitySeoPages\Pages\EditCommunitySeoPage;
use App\Filament\Resources\CommunitySeoPages\Pages\ListCommunitySeoPages;
use App\Models\CommunitySeoPage;
use App\Support\FilamentUploadPreview;
use App\Support\OpenGraph;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class CommunitySeoPageResource extends Resource
{
    protected static ?string $model = CommunitySeoPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    protected static ?string $navigationLabel = 'SEO страниц';

    protected static ?string $modelLabel = 'SEO страницы';

    protected static ?string $pluralModelLabel = 'SEO страниц сообщества';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 19;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Постоянная ссылка')
                ->description('Ссылки обновляются из раздела сообщества. Пустые SEO-поля используют данные страницы. Ручные настройки сохраняются при изменении адреса или заголовка.')
                ->schema([
                    TextInput::make('label')->label('Страница')->disabled()->dehydrated(false),
                    TextInput::make('path')->label('Постоянная ссылка')->disabled()->dehydrated(false),
                    Placeholder::make('access')->label('Доступность')
                        ->content(fn (CommunitySeoPage $record): string => $record->is_public
                            ? 'Публичная страница. Индексация управляется полем Robots.'
                            : 'Служебная или неопубликованная страница: индексация и sitemap отключены независимо от настроек.'),
                ])->columns(2)->columnSpanFull(),
            Section::make('Поисковая выдача')
                ->schema([
                    TextInput::make('settings.meta_title')->label('Meta title')->maxLength(255)->live(onBlur: true)
                        ->helperText('Рекомендуется до 60–70 символов. Без заполнения заголовок формируется автоматически.'),
                    TextInput::make('settings.seo_h1')->label('Заголовок H1')->maxLength(255)
                        ->helperText('Меняет основной заголовок страницы, сохраняя название темы или рубрики в списках.'),
                    Textarea::make('settings.meta_description')->label('Meta description')->rows(3)->maxLength(500)->live(onBlur: true)
                        ->helperText('Рекомендуется 120–160 символов. Настраивается отдельно от описания Open Graph.')->columnSpanFull(),
                    Textarea::make('settings.meta_keywords')->label('Meta keywords')->rows(2)->maxLength(500)->columnSpanFull(),
                    Select::make('settings.meta_robots')->label('Robots')->native(false)
                        ->options([
                            OpenGraph::ROBOTS_INDEX => 'Индексировать, переходить по ссылкам',
                            'index, nofollow' => 'Индексировать, не переходить по ссылкам',
                            'noindex, follow' => 'Не индексировать, переходить по ссылкам',
                            'noindex, nofollow' => 'Не индексировать',
                        ])->placeholder('По умолчанию для страницы'),
                    TextInput::make('settings.canonical_url')->label('Canonical URL')->url()->rules(['nullable', 'url:http,https'])->maxLength(1000)
                        ->placeholder(fn (CommunitySeoPage $record): string => $record->getUrl())
                        ->helperText('Пустое поле использует постоянную ссылку. Страница с другим canonical исключается из sitemap.'),
                    Toggle::make('settings.include_in_sitemap')->label('Включать в sitemap')->default(true)
                        ->helperText('Только для доступных индексируемых страниц с собственным canonical.'),
                    Placeholder::make('search_preview')->label('Предпросмотр поискового сниппета')
                        ->content(fn (Get $get, CommunitySeoPage $record): Htmlable => view('filament.community-seo-preview', [
                            'title' => $get('settings.meta_title') ?: $record->label.' — логистРу',
                            'description' => $get('settings.meta_description') ?: 'Описание будет сформировано из содержимого страницы.',
                            'url' => $get('settings.canonical_url') ?: $record->getUrl(),
                        ]))->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Open Graph')
                ->description('Превью ссылки в мессенджерах и социальных сетях.')
                ->schema([
                    TextInput::make('settings.og_title')->label('OG title')->maxLength(255),
                    Select::make('settings.og_type')->label('OG type')->options(['website' => 'website', 'article' => 'article', 'profile' => 'profile'])->native(false)->placeholder('Автоматически'),
                    Textarea::make('settings.og_description')->label('OG description')->rows(3)->maxLength(500)->columnSpanFull(),
                    self::image('og_image_path', 'OG image', 'og'),
                    TextInput::make('settings.og_image_alt')->label('Описание OG image (alt)')->maxLength(255)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Twitter / X Card')
                ->schema([
                    Select::make('settings.twitter_card')->label('Twitter card')->options(['summary_large_image' => 'summary_large_image', 'summary' => 'summary'])->native(false)->placeholder('summary_large_image'),
                    TextInput::make('settings.twitter_title')->label('Twitter title')->maxLength(255),
                    Textarea::make('settings.twitter_description')->label('Twitter description')->rows(3)->maxLength(500)->columnSpanFull(),
                    self::image('twitter_image_path', 'Twitter image', 'twitter'),
                    TextInput::make('settings.twitter_image_alt')->label('Описание Twitter image (alt)')->maxLength(255)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make('Schema.org')
                ->schema([
                    Toggle::make('settings.schema_enabled')->label('Выводить структурированные данные')->default(true),
                    Select::make('settings.schema_type')->label('Тип страницы')->native(false)->placeholder('Автоматически')
                        ->options(fn (CommunitySeoPage $record): array => ['WebPage' => 'WebPage'] + match ($record->kind) {
                            'post' => ['DiscussionForumPosting' => 'DiscussionForumPosting'],
                            'profile' => ['ProfilePage' => 'ProfilePage'],
                            default => ['CollectionPage' => 'CollectionPage'],
                        }),
                    TextInput::make('settings.schema_headline')->label('Schema headline')->maxLength(255)->columnSpanFull(),
                    Textarea::make('settings.schema_description')->label('Schema description')->rows(3)->maxLength(500)->columnSpanFull(),
                    self::image('schema_image_path', 'Schema image', 'schema'),
                ])->columns(2)->columnSpanFull(),
        ]);
    }

    private static function image(string $field, string $label, string $directory): FileUpload
    {
        return FileUpload::make('settings.'.$field)->label($label)->disk('public')
            ->directory('community/seo/'.$directory)->visibility('public')->image()
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])->maxSize(4096)
            ->imagePreviewHeight('120')->fetchFileInformation(false)->openable()->downloadable()
            ->getUploadedFileUsing(fn (FileUpload $component, string $file, string|array|null $storedFileNames): ?array => FilamentUploadPreview::resolve($component, $file, $storedFileNames))
            ->helperText('PNG, JPG или WebP до 4 МБ. Для превью рекомендуется 1200 × 630 px.')->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label')->label('Страница')->searchable()->sortable()->limit(65),
            TextColumn::make('kind')->label('Тип')->formatStateUsing(fn (string $state): string => CommunitySeoPage::KINDS[$state] ?? $state)->badge(),
            TextColumn::make('path')->label('Постоянная ссылка')->searchable()->copyable()
                ->copyableState(fn (CommunitySeoPage $record): string => $record->getUrl())->limit(70),
            IconColumn::make('is_public')->label('Публичная')->boolean(),
            TextColumn::make('settings.meta_title')->label('Meta title')->placeholder('Автоматически')->limit(45),
            TextColumn::make('settings.meta_robots')->label('Robots')->placeholder('По умолчанию')->limit(25),
            TextColumn::make('updated_at')->label('Обновлено')->dateTime('d.m.Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('kind')->label('Тип страницы')->options(CommunitySeoPage::KINDS),
        ])->defaultSort(fn (Builder $query): Builder => $query
            ->orderByRaw("CASE kind WHEN 'page' THEN 0 WHEN 'category' THEN 1 WHEN 'post' THEN 2 WHEN 'profile' THEN 3 ELSE 4 END")
            ->orderBy('label'))->recordActions([
                EditAction::make()->label('Настроить SEO'),
                Action::make('open')->label('Открыть')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CommunitySeoPage $record): string => $record->getUrl())->openUrlInNewTab(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunitySeoPages::route('/'), 'edit' => EditCommunitySeoPage::route('/{record}/edit')];
    }
}
