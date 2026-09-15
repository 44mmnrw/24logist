<?php

namespace App\Filament\Resources\CommunityComments;

use App\Filament\Resources\CommunityComments\Pages\EditCommunityComment;
use App\Filament\Resources\CommunityComments\Pages\ListCommunityComments;
use App\Filament\Resources\CommunityComments\Pages\ViewCommunityComment;
use App\Filament\Resources\CommunityComments\RelationManagers\ModerationActionsRelationManager;
use App\Filament\Resources\CommunityComments\RelationManagers\ReportsRelationManager;
use App\Models\CommunityComment;
use App\Models\CommunityUser;
use App\Services\Community\CommunityCommentModerationService;
use App\Services\Community\CommunityUserModerationService;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommunityCommentResource extends Resource
{
    protected static ?string $model = CommunityComment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static ?string $navigationLabel = 'Модерация комментариев';

    protected static ?string $modelLabel = 'комментарий';

    protected static ?string $pluralModelLabel = 'Модерация комментариев сообщества';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Содержание комментария')
                ->schema([
                    Textarea::make('body_markdown')
                        ->label('Текст в Markdown')
                        ->rows(12)
                        ->maxLength((int) config('community.limits.comment_body', 10000))
                        ->helperText('HTML обновится автоматически после сохранения.')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make('Состояние и связи')
                ->description('Статус меняется только отдельными действиями модерации с записью причины.')
                ->schema([
                    TextInput::make('author_name')
                        ->label('Автор')
                        ->formatStateUsing(fn (CommunityComment $record): string => $record->author?->displayName() ?? '[удалён]')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('post_title')
                        ->label('Тема')
                        ->formatStateUsing(fn (CommunityComment $record): string => $record->post?->title ?? '[удалена]')
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('status')
                        ->label('Статус')
                        ->options(CommunityComment::STATUS_LABELS)
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('score')->label('Рейтинг')->numeric()->disabled()->dehydrated(false),
                    TextInput::make('created_at')->label('Создан')->disabled()->dehydrated(false),
                    TextInput::make('edited_at')->label('Изменён')->disabled()->dehydrated(false),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Комментарий')
                ->schema([
                    TextEntry::make('author_name')
                        ->label('Автор')
                        ->state(fn (CommunityComment $record): string => $record->author?->displayName() ?? '[удалён]'),
                    TextEntry::make('author_username')
                        ->label('ID профиля')
                        ->state(fn (CommunityComment $record): string => $record->author ? '@'.$record->author->username : '—'),
                    TextEntry::make('post.title')
                        ->label('Тема')
                        ->url(fn (CommunityComment $record): ?string => $record->post?->trashed() ? null : $record->post?->getUrl())
                        ->openUrlInNewTab()
                        ->columnSpanFull(),
                    TextEntry::make('body_markdown')
                        ->label('Содержание')
                        ->markdown()
                        ->placeholder('Текст отсутствует')
                        ->columnSpanFull(),
                    ImageEntry::make('comment_photos')
                        ->label('Фотографии')
                        ->state(fn (CommunityComment $record): array => $record->photos->map(fn ($photo): string => $photo->getUrl())->all())
                        ->visible(fn (CommunityComment $record): bool => $record->photos->isNotEmpty())
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Состояние и статистика')
                ->schema([
                    TextEntry::make('status')
                        ->label('Статус')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => CommunityComment::STATUS_LABELS[$state] ?? $state)
                        ->color(fn (string $state): string => self::statusColor($state)),
                    TextEntry::make('open_reports_count')
                        ->label('Открытых жалоб')
                        ->state(fn (CommunityComment $record): int => $record->openReports()->count())
                        ->badge()
                        ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                    TextEntry::make('score')->label('Рейтинг')->numeric(),
                    TextEntry::make('depth')->label('Уровень ответа')->numeric(),
                    TextEntry::make('parent_id')->label('Ответ на комментарий')->placeholder('Корневой'),
                    TextEntry::make('created_at')->label('Создан')->dateTime('d.m.Y H:i:s'),
                    TextEntry::make('edited_at')->label('Изменён')->dateTime('d.m.Y H:i:s')->placeholder('—'),
                    TextEntry::make('deleted_at')->label('Удалён')->dateTime('d.m.Y H:i:s')->placeholder('—'),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body_markdown')->label('Комментарий')->limit(100)->wrap()->searchable(),
                TextColumn::make('author.username')->label('Автор')->searchable()->placeholder('[удалён]'),
                TextColumn::make('post.title')->label('Тема')->limit(55)->wrap()->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CommunityComment::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => self::statusColor($state)),
                TextColumn::make('open_reports_count')
                    ->label('Жалобы')
                    ->counts('openReports')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('score')->label('Рейтинг')->sortable(),
                TextColumn::make('depth')->label('Уровень')->sortable(),
                TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(CommunityComment::STATUS_LABELS),
                Filter::make('with_open_reports')
                    ->label('Только с открытыми жалобами')
                    ->query(fn (Builder $query): Builder => $query->whereHas('openReports')),
                Filter::make('restricted_authors')
                    ->label('Авторы с ограничениями')
                    ->query(fn (Builder $query): Builder => $query->whereHas('author', fn (Builder $query): Builder => $query
                        ->whereNotNull('banned_at')
                        ->orWhere('suspended_until', '>', now()))),
                TrashedFilter::make()->label('Удалённые комментарии'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (CommunityComment $record): string => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
                ActionGroup::make(static::moderationActions()),
            ])
            ->toolbarActions([BulkActionGroup::make(static::bulkModerationActions())]);
    }

    /** @return array<int, Action> */
    public static function moderationActions(): array
    {
        return [
            Action::make('open_public_comment')
                ->label('Открыть на сайте')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn (CommunityComment $record): ?string => $record->post?->trashed() ? null : $record->getUrl())
                ->openUrlInNewTab()
                ->visible(fn (CommunityComment $record): bool => ! $record->trashed() && $record->post !== null && ! $record->post->trashed()),
            Action::make('approve_comment')
                ->label('Одобрить / восстановить')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->schema([self::reasonField(required: false)])
                ->visible(fn (CommunityComment $record): bool => $record->status !== CommunityComment::STATUS_PUBLISHED || $record->trashed() || self::hasOpenReports($record))
                ->action(fn (CommunityComment $record, array $data) => self::runCommentModeration($record, CommunityCommentModerationService::ACTION_APPROVE, null, $data['reason'] ?? null)),
            Action::make('hide_comment')
                ->label('Скрыть комментарий')
                ->icon(Heroicon::OutlinedEyeSlash)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Комментарий будет заменён публичной пометкой о модерации. Ответы останутся видимыми.')
                ->schema(self::violationFields())
                ->visible(fn (CommunityComment $record): bool => ! $record->trashed() && $record->status === CommunityComment::STATUS_PUBLISHED)
                ->action(fn (CommunityComment $record, array $data) => self::runCommentModeration($record, CommunityCommentModerationService::ACTION_HIDE, $data['violation'], $data['reason'] ?? null)),
            Action::make('delete_comment')
                ->label('Удалить комментарий и ответы')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Удалить ветку комментариев?')
                ->modalDescription('Комментарий и все ответы будут сняты с публикации. Данные сохранятся для аудита и смогут быть восстановлены.')
                ->schema(self::violationFields())
                ->visible(fn (CommunityComment $record): bool => ! $record->trashed() && $record->status !== CommunityComment::STATUS_DELETED)
                ->action(function (CommunityComment $record, array $data) {
                    self::runCommentModeration($record, CommunityCommentModerationService::ACTION_DELETE, $data['violation'], $data['reason'] ?? null);

                    return redirect(static::getUrl());
                }),
            Action::make('force_delete_comment')
                ->label('Удалить окончательно')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Окончательно удалить ветку?')
                ->modalDescription('Это действие нельзя отменить. Комментарий, ответы, фотографии, реакции и жалобы будут удалены без возможности восстановления. Запись о решении останется в журнале аудита.')
                ->schema(self::purgeFields())
                ->visible(fn (CommunityComment $record): bool => $record->trashed() && $record->status === CommunityComment::STATUS_DELETED)
                ->action(function (CommunityComment $record, array $data) {
                    self::runPermanentDeletion($record, $data['reason']);

                    return redirect(static::getUrl());
                }),
            Action::make('warn_author')
                ->label('Предупредить автора')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning')
                ->schema([self::reasonField()])
                ->visible(fn (CommunityComment $record): bool => self::canModerateAuthor($record))
                ->action(fn (CommunityComment $record, array $data) => self::runUserModeration($record, CommunityUserModerationService::ACTION_WARN, $data['reason'])),
            Action::make('suspend_author')
                ->label('Временно ограничить автора')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->requiresConfirmation()
                ->schema([
                    Select::make('duration_days')
                        ->label('Срок ограничения')
                        ->options([1 => '1 день', 3 => '3 дня', 7 => '7 дней', 14 => '14 дней', 30 => '30 дней', 90 => '90 дней'])
                        ->default(7)
                        ->required(),
                    self::reasonField(),
                ])
                ->visible(fn (CommunityComment $record): bool => self::canModerateAuthor($record) && $record->author?->banned_at === null)
                ->action(fn (CommunityComment $record, array $data) => self::runUserModeration($record, CommunityUserModerationService::ACTION_SUSPEND, $data['reason'], (int) $data['duration_days'])),
            Action::make('ban_author')
                ->label('Заблокировать автора бессрочно')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                ->requiresConfirmation()
                ->schema([self::reasonField()])
                ->visible(fn (CommunityComment $record): bool => self::canModerateAuthor($record) && $record->author?->banned_at === null)
                ->action(fn (CommunityComment $record, array $data) => self::runUserModeration($record, CommunityUserModerationService::ACTION_BAN, $data['reason'])),
            Action::make('unrestrict_author')
                ->label('Снять ограничения с автора')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (CommunityComment $record): bool => self::canModerateAuthor($record) && $record->author?->isRestricted())
                ->action(fn (CommunityComment $record) => self::runUserModeration($record, CommunityUserModerationService::ACTION_UNRESTRICT)),
        ];
    }

    /** @return array<int, BulkAction> */
    private static function bulkModerationActions(): array
    {
        return [
            self::bulkAction('bulk_approve', 'Одобрить / восстановить', CommunityCommentModerationService::ACTION_APPROVE, 'success', false),
            self::bulkAction('bulk_hide', 'Скрыть', CommunityCommentModerationService::ACTION_HIDE, 'warning'),
            self::bulkAction('bulk_delete', 'Удалить вместе с ответами', CommunityCommentModerationService::ACTION_DELETE, 'danger'),
            self::bulkPurgeAction(),
        ];
    }

    private static function bulkPurgeAction(): BulkAction
    {
        return BulkAction::make('bulk_force_delete')
            ->label('Удалить окончательно')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Окончательно удалить выбранные ветки?')
            ->modalDescription('Действие нельзя отменить. Все связанные ответы, фотографии, реакции и жалобы будут удалены.')
            ->schema(self::purgeFields())
            ->action(function (Collection $records, array $data): void {
                $processed = 0;
                $selectedIds = $records->pluck('id')->map(fn ($id): int => (int) $id)->all();

                foreach ($records as $record) {
                    $fresh = CommunityComment::withTrashed()->find($record->getKey());
                    if ($fresh === null
                        || ! $fresh->trashed()
                        || $fresh->status !== CommunityComment::STATUS_DELETED
                        || self::hasSelectedAncestor($fresh, $selectedIds)) {
                        continue;
                    }

                    app(CommunityCommentModerationService::class)->purge(
                        $fresh,
                        self::adminId(),
                        $data['reason'],
                    );
                    $processed++;
                }

                Notification::make()->title('Окончательно удалено веток: '.$processed)->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function bulkAction(string $name, string $label, string $moderationAction, string $color, bool $violationRequired = true): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->color($color)
            ->requiresConfirmation()
            ->schema($violationRequired ? self::violationFields() : [self::reasonField(required: false)])
            ->action(function (Collection $records, array $data) use ($moderationAction): void {
                $processed = 0;
                $selectedIds = $records->pluck('id')->map(fn ($id): int => (int) $id)->all();
                foreach ($records as $record) {
                    $fresh = CommunityComment::withTrashed()->find($record->getKey());
                    if ($fresh === null
                        || ($moderationAction === CommunityCommentModerationService::ACTION_DELETE && self::hasSelectedAncestor($fresh, $selectedIds))
                        || ($moderationAction === CommunityCommentModerationService::ACTION_HIDE && ($fresh->trashed() || $fresh->status !== CommunityComment::STATUS_PUBLISHED))
                        || ($moderationAction === CommunityCommentModerationService::ACTION_DELETE && ($fresh->trashed() || $fresh->status === CommunityComment::STATUS_DELETED))) {
                        continue;
                    }
                    app(CommunityCommentModerationService::class)->moderate(
                        $fresh,
                        $moderationAction,
                        self::adminId(),
                        $data['violation'] ?? null,
                        $data['reason'] ?? null,
                    );
                    $processed++;
                }

                Notification::make()->title('Обработано комментариев: '.$processed)->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /** @return array<int, Select|Textarea> */
    private static function violationFields(): array
    {
        return [
            Select::make('violation')->label('Нарушение')->options(CommunityCommentModerationService::VIOLATION_LABELS)->required(),
            self::reasonField(required: false),
        ];
    }

    private static function reasonField(bool $required = true): Textarea
    {
        return Textarea::make('reason')->label('Комментарий модератора')->required($required)->maxLength(1000)->rows(4);
    }

    /** @return array<int, TextInput|Textarea> */
    private static function purgeFields(): array
    {
        return [
            TextInput::make('confirmation')
                ->label('Введите УДАЛИТЬ для подтверждения')
                ->rules(['required', 'in:УДАЛИТЬ'])
                ->validationMessages(['in' => 'Для подтверждения введите слово УДАЛИТЬ.']),
            self::reasonField(),
        ];
    }

    private static function runCommentModeration(CommunityComment $comment, string $action, ?string $violation = null, ?string $reason = null): void
    {
        app(CommunityCommentModerationService::class)->moderate($comment, $action, self::adminId(), $violation, $reason);
        Notification::make()->title('Действие модерации выполнено')->success()->send();
    }

    private static function runPermanentDeletion(CommunityComment $comment, string $reason): void
    {
        app(CommunityCommentModerationService::class)->purge($comment, self::adminId(), $reason);
        Notification::make()->title('Комментарий окончательно удалён')->success()->send();
    }

    private static function runUserModeration(CommunityComment $comment, string $action, ?string $reason = null, ?int $durationDays = null): void
    {
        $author = $comment->author;
        if ($author instanceof CommunityUser) {
            app(CommunityUserModerationService::class)->moderate($author, $action, self::adminId(), $reason, $durationDays);
        }
        Notification::make()->title('Мера к автору применена')->success()->send();
    }

    private static function canModerateAuthor(CommunityComment $comment): bool
    {
        return $comment->author instanceof CommunityUser && ! $comment->author->trashed();
    }

    private static function adminId(): ?int
    {
        $id = Filament::auth()->id();

        return $id === null ? null : (int) $id;
    }

    private static function hasOpenReports(CommunityComment $comment): bool
    {
        return (int) ($comment->getAttribute('open_reports_count') ?? $comment->openReports()->count()) > 0;
    }

    /** @param list<int> $selectedIds */
    private static function hasSelectedAncestor(CommunityComment $comment, array $selectedIds): bool
    {
        $parentId = $comment->parent_id;
        $visited = [];

        while ($parentId !== null && ! in_array($parentId, $visited, true)) {
            if (in_array((int) $parentId, $selectedIds, true)) {
                return true;
            }

            $visited[] = (int) $parentId;
            $parent = CommunityComment::withTrashed()->find($parentId);
            if ($parent === null || $parent->community_post_id !== $comment->community_post_id) {
                return false;
            }
            $parentId = $parent->parent_id;
        }

        return false;
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            CommunityComment::STATUS_PUBLISHED => 'success',
            CommunityComment::STATUS_HIDDEN => 'warning',
            CommunityComment::STATUS_DELETED => 'danger',
            default => 'gray',
        };
    }

    public static function getRelations(): array
    {
        return [ReportsRelationManager::class, ModerationActionsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityComments::route('/'),
            'view' => ViewCommunityComment::route('/{record}'),
            'edit' => EditCommunityComment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = CommunityComment::query()->whereHas('openReports')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
