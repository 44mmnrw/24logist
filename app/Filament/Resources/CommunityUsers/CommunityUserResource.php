<?php

namespace App\Filament\Resources\CommunityUsers;

use App\Filament\Resources\CommunityUsers\Pages\EditCommunityUser;
use App\Filament\Resources\CommunityUsers\Pages\ListCommunityUsers;
use App\Filament\Resources\CommunityUsers\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\CommunityUsers\RelationManagers\IdentitiesRelationManager;
use App\Filament\Resources\CommunityUsers\RelationManagers\ModerationActionsRelationManager;
use App\Filament\Resources\CommunityUsers\RelationManagers\PostsRelationManager;
use App\Filament\Resources\CommunityUsers\RelationManagers\SessionsRelationManager;
use App\Models\CommunityUser;
use App\Services\Community\CommunityUserModerationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommunityUserResource extends Resource
{
    protected static ?string $model = CommunityUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Участники';

    protected static ?string $modelLabel = 'участник';

    protected static ?string $pluralModelLabel = 'Участники сообщества';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('username')->label('ID профиля')->disabled(),
            TextInput::make('display_name')->label('Никнейм')->required()->maxLength(50),
            TextInput::make('first_name')->label('Имя')->maxLength(50),
            Toggle::make('show_first_name')->label('Показывать имя'),
            TextInput::make('last_name')->label('Фамилия')->maxLength(50),
            Toggle::make('show_last_name')->label('Показывать фамилию'),
            Select::make('transport_role')->label('Роль в перевозках')->options(CommunityUser::TRANSPORT_ROLES)->placeholder('Не указана'),
            Select::make('role')->label('Права в сообществе')->options(['user' => 'Участник', 'moderator' => 'Модератор'])->required(),
            Textarea::make('bio')->label('О себе')->rows(5)->maxLength(1000)->columnSpanFull(),
            TextInput::make('karma')->label('Рейтинг')->numeric()->disabled(),
            TextInput::make('avatar_source')->label('Источник аватара')->disabled()->placeholder('—'),
            DateTimePicker::make('onboarded_at')->label('Профиль заполнен')->disabled()->dehydrated(false),
            DateTimePicker::make('terms_accepted_at')->label('Правила приняты')->disabled()->dehydrated(false),
            DateTimePicker::make('last_login_at')->label('Последний вход')->disabled()->dehydrated(false),
            DateTimePicker::make('last_seen_at')->label('Последняя активность')->disabled()->dehydrated(false),
            TextInput::make('last_login_ip')->label('IP последнего входа')->disabled()->dehydrated(false)->placeholder('—'),
            Textarea::make('last_user_agent')->label('Последний User-Agent')->disabled()->dehydrated(false)->rows(3)->columnSpanFull(),
            DateTimePicker::make('suspended_until')->label('Ограничен до')->disabled()->dehydrated(false),
            DateTimePicker::make('banned_at')->label('Заблокирован с')->disabled()->dehydrated(false),
            DateTimePicker::make('created_at')->label('Дата регистрации')->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_path')->label('Аватар')->disk('public')->circular()->defaultImageUrl(null),
                TextColumn::make('display_name')->label('Никнейм')->searchable()->sortable(),
                TextColumn::make('username')->label('ID')->searchable()->sortable(),
                TextColumn::make('moderation_status')
                    ->label('Статус')
                    ->state(fn (CommunityUser $record): string => self::moderationStatus($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Активен' => 'success',
                        'Временно ограничен' => 'warning',
                        'Заблокирован', 'Удалён' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('warnings_count')
                    ->label('Предупреждения')
                    ->counts('warnings')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('transport_role')->label('В перевозках')->formatStateUsing(fn (?string $state): string => CommunityUser::TRANSPORT_ROLES[$state] ?? 'Не указана'),
                TextColumn::make('role')->label('Права')->badge(),
                TextColumn::make('karma')->label('Рейтинг')->sortable(),
                TextColumn::make('posts_count')->label('Тем')->counts('posts'),
                TextColumn::make('comments_count')->label('Комментариев')->counts('comments'),
                TextColumn::make('suspended_until')->label('Ограничен до')->dateTime('d.m.Y H:i')->placeholder('—'),
                TextColumn::make('created_at')->label('Дата регистрации')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('last_seen_at')->label('Активность')->dateTime('d.m.Y H:i')->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('ai_personas')
                    ->label('AI-персонажи')
                    ->options([
                        'exclude' => 'Не показывать',
                        'only' => 'Только AI-персонажи',
                        'all' => 'Показывать всех',
                    ])
                    ->default('exclude')
                    ->native(false)
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? 'exclude') {
                        'only' => $query->whereHas('aiPersona'),
                        'all' => $query,
                        default => $query->whereDoesntHave('aiPersona'),
                    }),
                SelectFilter::make('role')->label('Права')->options(['user' => 'Участник', 'moderator' => 'Модератор']),
                Filter::make('restricted')
                    ->label('Только ограниченные')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                        $query->whereNotNull('banned_at')
                            ->orWhere('suspended_until', '>', now());
                    })),
                Filter::make('with_warnings')
                    ->label('С предупреждениями')
                    ->query(fn (Builder $query): Builder => $query->whereHas('warnings')),
                TrashedFilter::make()->label('Удалённые аккаунты'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->visible(fn (CommunityUser $record): bool => ! $record->trashed()),
                ActionGroup::make(self::moderationActions()),
            ]);
    }

    /** @return array<int, Action> */
    private static function moderationActions(): array
    {
        return [
            Action::make('warn_user')
                ->label('Выдать предупреждение')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->color('warning')
                ->schema([self::reasonField('Текст предупреждения')])
                ->visible(fn (CommunityUser $record): bool => ! $record->trashed())
                ->action(fn (CommunityUser $record, array $data) => self::runModeration(
                    $record,
                    CommunityUserModerationService::ACTION_WARN,
                    $data['reason'],
                )),
            Action::make('suspend_user')
                ->label('Временно ограничить')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Участник не сможет создавать темы, комментировать, голосовать и ставить реакции до окончания срока.')
                ->schema([
                    Select::make('duration_days')
                        ->label('Срок ограничения')
                        ->options([
                            1 => '1 день',
                            3 => '3 дня',
                            7 => '7 дней',
                            14 => '14 дней',
                            30 => '30 дней',
                            90 => '90 дней',
                        ])
                        ->default(7)
                        ->required(),
                    self::reasonField(),
                ])
                ->visible(fn (CommunityUser $record): bool => ! $record->trashed() && $record->banned_at === null)
                ->action(fn (CommunityUser $record, array $data) => self::runModeration(
                    $record,
                    CommunityUserModerationService::ACTION_SUSPEND,
                    $data['reason'],
                    (int) $data['duration_days'],
                )),
            Action::make('ban_user')
                ->label('Заблокировать бессрочно')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Заблокировать участника?')
                ->modalDescription('Пользователь сможет просматривать сообщество, но не сможет публиковать материалы и взаимодействовать с ними.')
                ->schema([self::reasonField()])
                ->visible(fn (CommunityUser $record): bool => ! $record->trashed() && $record->banned_at === null)
                ->action(fn (CommunityUser $record, array $data) => self::runModeration(
                    $record,
                    CommunityUserModerationService::ACTION_BAN,
                    $data['reason'],
                )),
            Action::make('unrestrict_user')
                ->label('Снять ограничения')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (CommunityUser $record): bool => ! $record->trashed() && $record->isRestricted())
                ->action(fn (CommunityUser $record) => self::runModeration(
                    $record,
                    CommunityUserModerationService::ACTION_UNRESTRICT,
                )),
            Action::make('delete_user')
                ->label('Удалить аккаунт')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Удалить участника?')
                ->modalDescription('Аккаунт будет скрыт, но материалы и история модерации сохранятся. Удаление можно отменить.')
                ->schema([self::reasonField()])
                ->visible(fn (CommunityUser $record): bool => ! $record->trashed())
                ->action(fn (CommunityUser $record, array $data) => self::runModeration(
                    $record,
                    CommunityUserModerationService::ACTION_DELETE,
                    $data['reason'],
                )),
            Action::make('restore_user')
                ->label('Восстановить аккаунт')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (CommunityUser $record): bool => $record->trashed())
                ->action(fn (CommunityUser $record) => self::runModeration(
                    $record,
                    CommunityUserModerationService::ACTION_RESTORE,
                )),
        ];
    }

    private static function reasonField(string $label = 'Причина / комментарий модератора'): Textarea
    {
        return Textarea::make('reason')
            ->label($label)
            ->required()
            ->maxLength(1000)
            ->rows(4);
    }

    private static function runModeration(
        CommunityUser $user,
        string $action,
        ?string $reason = null,
        ?int $durationDays = null,
    ): void {
        app(CommunityUserModerationService::class)->moderate(
            $user,
            $action,
            self::adminId(),
            $reason,
            $durationDays,
        );

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

    private static function moderationStatus(CommunityUser $user): string
    {
        return match (true) {
            $user->trashed() => 'Удалён',
            $user->banned_at !== null => 'Заблокирован',
            $user->suspended_until?->isFuture() === true => 'Временно ограничен',
            default => 'Активен',
        };
    }

    public static function getRelations(): array
    {
        return [
            IdentitiesRelationManager::class,
            SessionsRelationManager::class,
            PostsRelationManager::class,
            CommentsRelationManager::class,
            ModerationActionsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunityUsers::route('/'), 'edit' => EditCommunityUser::route('/{record}/edit')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
