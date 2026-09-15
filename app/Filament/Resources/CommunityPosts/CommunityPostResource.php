<?php

namespace App\Filament\Resources\CommunityPosts;

use App\Filament\Resources\CommunityPosts\Pages\EditCommunityPost;
use App\Filament\Resources\CommunityPosts\Pages\ListCommunityPosts;
use App\Filament\Resources\CommunityPosts\Pages\ViewCommunityPost;
use App\Filament\Resources\CommunityPosts\RelationManagers\ModerationActionsRelationManager;
use App\Filament\Resources\CommunityPosts\RelationManagers\ReportsRelationManager;
use App\Models\CommunityPost;
use App\Services\Community\CommunityPostModerationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommunityPostResource extends Resource
{
    protected static ?string $model = CommunityPost::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Модерация тем';

    protected static ?string $modelLabel = 'тема';

    protected static ?string $pluralModelLabel = 'Модерация тем сообщества';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Содержание темы')
                ->schema([
                    TextInput::make('title')
                        ->label('Заголовок')
                        ->required()
                        ->maxLength(180)
                        ->columnSpanFull(),
                    Select::make('community_category_id')
                        ->relationship('category', 'name')
                        ->label('Рубрика')
                        ->required(),
                    Textarea::make('body_markdown')
                        ->label('Текст в Markdown')
                        ->rows(14)
                        ->maxLength((int) config('community.limits.post_body', 20000))
                        ->helperText('HTML обновится автоматически после сохранения.')
                        ->columnSpanFull(),
                    TextInput::make('external_url')
                        ->label('Внешняя ссылка')
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Состояние')
                ->description('Статус, закрепление и блокировка меняются только отдельными действиями модерации.')
                ->schema([
                    Select::make('status')
                        ->label('Статус')
                        ->options(CommunityPost::STATUS_LABELS)
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('score')->label('Рейтинг')->numeric()->disabled()->dehydrated(false),
                    TextInput::make('comments_count')->label('Комментариев')->numeric()->disabled()->dehydrated(false),
                    TextInput::make('published_at')->label('Опубликована')->disabled()->dehydrated(false),
                    TextInput::make('locked_at')->label('Обсуждение закрыто')->disabled()->dehydrated(false),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Публикация')
                ->schema([
                    TextEntry::make('title')->label('Заголовок')->columnSpanFull(),
                    TextEntry::make('author_name')
                        ->label('Автор')
                        ->state(fn (CommunityPost $record): string => $record->author?->displayName() ?? '[удалён]'),
                    TextEntry::make('category.name')->label('Рубрика'),
                    TextEntry::make('body_markdown')
                        ->label('Содержание')
                        ->markdown()
                        ->placeholder('Текст отсутствует')
                        ->columnSpanFull(),
                    TextEntry::make('external_url')
                        ->label('Внешняя ссылка')
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->placeholder('—')
                        ->columnSpanFull(),
                    ImageEntry::make('post_photos')
                        ->label('Фотографии')
                        ->state(fn (CommunityPost $record): array => $record->photos->map(fn ($photo): string => $photo->getUrl())->all())
                        ->visible(fn (CommunityPost $record): bool => $record->photos->isNotEmpty())
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Состояние и статистика')
                ->schema([
                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => CommunityPost::STATUS_LABELS[$state] ?? $state)
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('open_reports_count')
                        ->label('Открытых жалоб')
                        ->state(fn (CommunityPost $record): int => $record->openReports()->count())
                        ->badge()
                        ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                    TextEntry::make('score')->label('Рейтинг')->numeric(),
                    TextEntry::make('comments_count')->label('Комментариев')->numeric(),
                    TextEntry::make('is_pinned')->label('Закреплена')->formatStateUsing(fn (bool $state): string => $state ? 'Да' : 'Нет'),
                    TextEntry::make('locked_at')->label('Обсуждение закрыто')->dateTime('d.m.Y H:i')->placeholder('Нет'),
                    TextEntry::make('published_at')->label('Опубликована')->dateTime('d.m.Y H:i')->placeholder('—'),
                    TextEntry::make('created_at')->label('Создана')->dateTime('d.m.Y H:i'),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Тема')
                    ->searchable()
                    ->sortable()
                    ->limit(70)
                    ->wrap(),
                TextColumn::make('author.username')
                    ->label('Автор')
                    ->searchable()
                    ->placeholder('[удалён]'),
                TextColumn::make('category.name')->label('Рубрика')->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CommunityPost::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => self::statusColor($state)),
                TextColumn::make('open_reports_count')
                    ->label('Жалобы')
                    ->counts('openReports')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('score')->label('Рейтинг')->sortable(),
                TextColumn::make('comments_count')->label('Комментарии')->sortable(),
                IconColumn::make('is_pinned')->label('Закреплена')->boolean(),
                IconColumn::make('is_locked')
                    ->label('Закрыта')
                    ->state(fn (CommunityPost $record): bool => $record->locked_at !== null)
                    ->boolean(),
                TextColumn::make('created_at')->label('Создана')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(CommunityPost::STATUS_LABELS),
                SelectFilter::make('community_category_id')
                    ->label('Рубрика')
                    ->relationship('category', 'name')
                    ->preload(),
                TernaryFilter::make('is_pinned')
                    ->label('Закрепление')
                    ->trueLabel('Только закреплённые')
                    ->falseLabel('Только незакреплённые'),
                TernaryFilter::make('locked_at')
                    ->label('Обсуждение')
                    ->nullable()
                    ->trueLabel('Только закрытые')
                    ->falseLabel('Только открытые'),
                Filter::make('with_open_reports')
                    ->label('Только с открытыми жалобами')
                    ->query(fn (Builder $query): Builder => $query->whereHas('openReports')),
                TrashedFilter::make()
                    ->label('Удалённые темы'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (CommunityPost $record): string => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
                ActionGroup::make(static::moderationActions()),
            ])
            ->toolbarActions([
                BulkActionGroup::make(static::bulkModerationActions()),
            ]);
    }

    /** @return array<int, Action> */
    public static function moderationActions(): array
    {
        return [
            Action::make('open_public_post')
                ->label('Открыть на сайте')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (CommunityPost $record): string => $record->getUrl())
                ->openUrlInNewTab()
                ->visible(fn (CommunityPost $record): bool => $record->status === CommunityPost::STATUS_PUBLISHED),
            Action::make('approve_post')
                ->label('Одобрить / восстановить')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->schema([self::reasonField(required: false)])
                ->visible(fn (CommunityPost $record): bool => $record->status !== CommunityPost::STATUS_PUBLISHED || self::hasOpenReports($record))
                ->action(fn (CommunityPost $record, array $data) => self::runModeration($record, CommunityPostModerationService::ACTION_APPROVE, $data['reason'] ?? null)),
            Action::make('hide_post')
                ->label('Скрыть')
                ->icon(Heroicon::OutlinedEyeSlash)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Тема исчезнет из сообщества. Открытые жалобы будут отмечены как рассмотренные.')
                ->schema([self::reasonField()])
                ->visible(fn (CommunityPost $record): bool => $record->status === CommunityPost::STATUS_PUBLISHED)
                ->action(fn (CommunityPost $record, array $data) => self::runModeration($record, CommunityPostModerationService::ACTION_HIDE, $data['reason'])),
            Action::make('delete_post')
                ->label('Удалить модератором')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Удалить тему из сообщества?')
                ->modalDescription('Содержимое сохранится в админке и журнале, но тема больше не будет доступна публично.')
                ->schema([self::reasonField()])
                ->visible(fn (CommunityPost $record): bool => $record->status !== CommunityPost::STATUS_DELETED)
                ->action(function (CommunityPost $record, array $data) {
                    self::runModeration($record, CommunityPostModerationService::ACTION_DELETE, $data['reason']);

                    return redirect(static::getUrl());
                }),
            Action::make('lock_post')
                ->label('Закрыть обсуждение')
                ->icon(Heroicon::OutlinedLockClosed)
                ->requiresConfirmation()
                ->schema([self::reasonField(required: false)])
                ->visible(fn (CommunityPost $record): bool => $record->status === CommunityPost::STATUS_PUBLISHED && $record->locked_at === null)
                ->action(fn (CommunityPost $record, array $data) => self::runModeration($record, CommunityPostModerationService::ACTION_LOCK, $data['reason'] ?? null)),
            Action::make('unlock_post')
                ->label('Открыть обсуждение')
                ->icon(Heroicon::OutlinedLockOpen)
                ->visible(fn (CommunityPost $record): bool => $record->locked_at !== null)
                ->action(fn (CommunityPost $record) => self::runModeration($record, CommunityPostModerationService::ACTION_UNLOCK)),
            Action::make('pin_post')
                ->label('Закрепить')
                ->icon(Heroicon::OutlinedBookmark)
                ->visible(fn (CommunityPost $record): bool => $record->status === CommunityPost::STATUS_PUBLISHED && ! $record->is_pinned)
                ->action(fn (CommunityPost $record) => self::runModeration($record, CommunityPostModerationService::ACTION_PIN)),
            Action::make('unpin_post')
                ->label('Открепить')
                ->icon(Heroicon::OutlinedBookmarkSlash)
                ->visible(fn (CommunityPost $record): bool => $record->is_pinned)
                ->action(fn (CommunityPost $record) => self::runModeration($record, CommunityPostModerationService::ACTION_UNPIN)),
        ];
    }

    /** @return array<int, BulkAction> */
    private static function bulkModerationActions(): array
    {
        return [
            self::bulkAction('bulk_approve', 'Одобрить / восстановить', CommunityPostModerationService::ACTION_APPROVE, 'success', false),
            self::bulkAction('bulk_hide', 'Скрыть', CommunityPostModerationService::ACTION_HIDE, 'warning'),
            self::bulkAction('bulk_lock', 'Закрыть обсуждения', CommunityPostModerationService::ACTION_LOCK, 'gray', false),
            self::bulkAction('bulk_unlock', 'Открыть обсуждения', CommunityPostModerationService::ACTION_UNLOCK, 'gray', false),
            self::bulkAction('bulk_delete', 'Удалить модератором', CommunityPostModerationService::ACTION_DELETE, 'danger'),
        ];
    }

    private static function bulkAction(string $name, string $label, string $moderationAction, string $color, bool $reasonRequired = true): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->color($color)
            ->requiresConfirmation()
            ->schema([self::reasonField($reasonRequired)])
            ->action(function (Collection $records, array $data) use ($moderationAction): void {
                foreach ($records as $record) {
                    app(CommunityPostModerationService::class)->moderate(
                        $record,
                        $moderationAction,
                        self::adminId(),
                        $data['reason'] ?? null,
                    );
                }

                Notification::make()
                    ->title('Обработано тем: '.$records->count())
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function reasonField(bool $required = true): Textarea
    {
        return Textarea::make('reason')
            ->label('Причина / комментарий модератора')
            ->required($required)
            ->maxLength(1000)
            ->rows(4);
    }

    private static function runModeration(CommunityPost $post, string $action, ?string $reason = null): void
    {
        app(CommunityPostModerationService::class)->moderate($post, $action, self::adminId(), $reason);

        Notification::make()
            ->title('Действие модерации выполнено')
            ->success()
            ->send();
    }

    private static function adminId(): ?int
    {
        $id = Filament::auth()->id();

        return $id === null ? null : (int) $id;
    }

    private static function hasOpenReports(CommunityPost $post): bool
    {
        return (int) ($post->getAttribute('open_reports_count') ?? $post->openReports()->count()) > 0;
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            CommunityPost::STATUS_PUBLISHED => 'success',
            CommunityPost::STATUS_HIDDEN => 'warning',
            CommunityPost::STATUS_DELETED => 'danger',
            default => 'gray',
        };
    }

    public static function getRelations(): array
    {
        return [
            ReportsRelationManager::class,
            ModerationActionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityPosts::route('/'),
            'view' => ViewCommunityPost::route('/{record}'),
            'edit' => EditCommunityPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CommunityPost::query()->whereHas('openReports')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
