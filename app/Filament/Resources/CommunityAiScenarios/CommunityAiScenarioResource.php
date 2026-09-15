<?php

namespace App\Filament\Resources\CommunityAiScenarios;

use App\Filament\Resources\CommunityAiScenarios\Pages\CreateCommunityAiScenario;
use App\Filament\Resources\CommunityAiScenarios\Pages\EditCommunityAiScenario;
use App\Filament\Resources\CommunityAiScenarios\Pages\ListCommunityAiScenarios;
use App\Filament\Resources\CommunityAiScenarios\RelationManagers\StepsRelationManager;
use App\Jobs\PrepareCommunityAiScenario;
use App\Models\CommunityAiScenario;
use App\Models\CommunityAiSource;
use App\Services\Community\CommunityAiScenarioPublisher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunityAiScenarioResource extends Resource
{
    protected static ?string $model = CommunityAiScenario::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'AI-сценарии';

    protected static ?string $modelLabel = 'AI-сценарий';

    protected static ?string $pluralModelLabel = 'AI-сценарии обсуждений';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Исходные данные')
                ->schema([
                    Select::make('source_ids')
                        ->label('Чаты MAX')
                        ->multiple()
                        ->options(fn (): array => CommunityAiSource::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                    DateTimePicker::make('source_from')
                        ->label('Начало периода')
                        ->default(fn () => now()->subDay()->startOfDay())
                        ->required(),
                    DateTimePicker::make('source_to')
                        ->label('Конец периода')
                        ->default(fn () => now()->subDay()->endOfDay())
                        ->after('source_from')
                        ->required(),
                    TagsInput::make('scan_keywords')
                        ->label('Ключевые слова и фразы')
                        ->placeholder('Например: ЭТрН, простой на погрузке')
                        ->helperText('Сообщения будут отобраны по любому из указанных слов или фраз. Для сохранения контекста добавятся соседние реплики. Оставьте пустым, чтобы анализировать весь период.')
                        ->columnSpanFull(),
                    Select::make('community_category_id')
                        ->relationship('category', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true)->where('posting_enabled', true))
                        ->label('Рубрика')
                        ->placeholder('Выберет редактор'),
                    DateTimePicker::make('planned_at')
                        ->label('Начать публикацию')
                        ->helperText('Если дата в прошлом или не указана, сценарий начнётся сразу.'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Редакторский бриф')
                ->schema([
                    TextInput::make('title')->label('Рабочее название')->maxLength(180)->columnSpanFull(),
                    Textarea::make('editor_brief')->label('Бриф')->rows(8)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make('Состояние')
                ->schema([
                    Select::make('status')->label('Статус')->options(CommunityAiScenario::STATUS_LABELS)->default(CommunityAiScenario::STATUS_DRAFT)->disabled()->dehydrated(),
                    Textarea::make('last_error')->label('Последняя ошибка')->disabled()->dehydrated(false)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Сценарий')->placeholder('Без названия')->searchable()->wrap(),
                TextColumn::make('status')->label('Статус')->badge()->formatStateUsing(fn (string $state): string => CommunityAiScenario::STATUS_LABELS[$state] ?? $state)->color(fn (string $state): string => self::statusColor($state)),
                TextColumn::make('category.name')->label('Рубрика')->placeholder('—'),
                TextColumn::make('steps_count')->label('Шагов')->counts('steps'),
                TextColumn::make('source_from')->label('Период чатов')->dateTime('d.m.Y H:i')->description(fn (CommunityAiScenario $record): string => 'до '.$record->source_to->format('d.m.Y H:i')),
                TextColumn::make('scan_keywords')
                    ->label('Ключевые слова')
                    ->badge()
                    ->separator(', ')
                    ->placeholder('Весь период')
                    ->toggleable(),
                TextColumn::make('planned_at')->label('Запуск')->dateTime('d.m.Y H:i')->placeholder('Сразу'),
                TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Статус')->options(CommunityAiScenario::STATUS_LABELS),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
                ActionGroup::make(self::scenarioActions()),
            ]);
    }

    /** @return array<int, Action> */
    public static function scenarioActions(): array
    {
        return [
            Action::make('prepare')
                ->label('Сканировать и создать черновики')
                ->icon(Heroicon::OutlinedCpuChip)
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('Сообщения будут импортированы из MAX, а затем Timeweb AI создаст бриф и черновики. Ничего не будет опубликовано.')
                ->visible(fn (CommunityAiScenario $record): bool => in_array($record->status, [CommunityAiScenario::STATUS_DRAFT, CommunityAiScenario::STATUS_REVIEW, CommunityAiScenario::STATUS_FAILED], true))
                ->action(function (CommunityAiScenario $record): void {
                    $record->update(['status' => CommunityAiScenario::STATUS_QUEUED, 'last_error' => null]);
                    PrepareCommunityAiScenario::dispatch($record->id);
                    Notification::make()->title('Сценарий поставлен в очередь')->success()->send();
                }),
            Action::make('approve')
                ->label('Одобрить все черновики')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (CommunityAiScenario $record): bool => $record->status === CommunityAiScenario::STATUS_REVIEW)
                ->action(function (CommunityAiScenario $record): void {
                    abort_unless($record->steps()->where('type', 'topic')->whereNotNull('draft_body')->exists(), 422, 'Нет черновика темы.');
                    $record->steps()->where('status', 'pending_review')->update(['status' => 'approved']);
                    $record->update(['status' => CommunityAiScenario::STATUS_APPROVED, 'last_error' => null]);
                    Notification::make()->title('Черновики одобрены')->success()->send();
                }),
            Action::make('start')
                ->label('Запустить публикацию')
                ->icon(Heroicon::OutlinedPlay)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (CommunityAiScenario $record): bool => in_array($record->status, [CommunityAiScenario::STATUS_APPROVED, CommunityAiScenario::STATUS_PAUSED], true))
                ->action(function (CommunityAiScenario $record): void {
                    app(CommunityAiScenarioPublisher::class)->schedule($record);
                    Notification::make()->title('Сценарий запланирован')->success()->send();
                }),
            Action::make('pause')
                ->label('Приостановить')
                ->icon(Heroicon::OutlinedPause)
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (CommunityAiScenario $record): bool => in_array($record->status, [CommunityAiScenario::STATUS_SCHEDULED, CommunityAiScenario::STATUS_RUNNING], true))
                ->action(fn (CommunityAiScenario $record) => $record->update(['status' => CommunityAiScenario::STATUS_PAUSED])),
            Action::make('cancel')
                ->label('Отменить сценарий')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (CommunityAiScenario $record): bool => ! in_array($record->status, [CommunityAiScenario::STATUS_COMPLETED, CommunityAiScenario::STATUS_CANCELLED], true))
                ->action(function (CommunityAiScenario $record): void {
                    $record->steps()->whereIn('status', ['draft', 'pending_review', 'approved', 'scheduled'])->update(['status' => 'cancelled']);
                    $record->update(['status' => CommunityAiScenario::STATUS_CANCELLED]);
                }),
        ];
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            CommunityAiScenario::STATUS_REVIEW, CommunityAiScenario::STATUS_PAUSED => 'warning',
            CommunityAiScenario::STATUS_APPROVED, CommunityAiScenario::STATUS_COMPLETED => 'success',
            CommunityAiScenario::STATUS_FAILED, CommunityAiScenario::STATUS_CANCELLED => 'danger',
            CommunityAiScenario::STATUS_SCHEDULED, CommunityAiScenario::STATUS_RUNNING, CommunityAiScenario::STATUS_QUEUED, CommunityAiScenario::STATUS_PREPARING => 'info',
            default => 'gray',
        };
    }

    public static function getRelations(): array
    {
        return [StepsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityAiScenarios::route('/'),
            'create' => CreateCommunityAiScenario::route('/create'),
            'edit' => EditCommunityAiScenario::route('/{record}/edit'),
        ];
    }
}
