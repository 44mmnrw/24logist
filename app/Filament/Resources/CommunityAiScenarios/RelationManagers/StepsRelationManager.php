<?php

namespace App\Filament\Resources\CommunityAiScenarios\RelationManagers;

use App\Models\CommunityAiPersona;
use App\Models\CommunityAiScenarioStep;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class StepsRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    protected static ?string $title = 'Черновики и расписание';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('community_ai_persona_id')
                ->label('Персона')
                ->options(fn (): array => CommunityAiPersona::query()->with('communityUser')->get()->mapWithKeys(
                    fn (CommunityAiPersona $persona): array => [$persona->id => $persona->communityUser->displayName().' (@'.$persona->communityUser->username.')'],
                )->all())
                ->required(),
            Select::make('type')->label('Тип')->options(['topic' => 'Тема', 'comment' => 'Комментарий'])->disabled()->dehydrated(),
            Select::make('parent_step_id')
                ->label('Ответ на комментарий')
                ->options(function (?CommunityAiScenarioStep $record): array {
                    if ($record === null || $record->type !== 'comment') {
                        return [];
                    }

                    return $record->scenario->steps()
                        ->with('persona.communityUser')
                        ->where('type', 'comment')
                        ->where('sequence', '<', $record->sequence)
                        ->whereNull('parent_step_id')
                        ->get()
                        ->mapWithKeys(fn (CommunityAiScenarioStep $step): array => [
                            $step->id => '#'.$step->sequence.' '.$step->persona->communityUser->displayName().': '.Str::limit((string) $step->draft_body, 80),
                        ])
                        ->all();
                })
                ->placeholder('Основная тема')
                ->visible(fn (?CommunityAiScenarioStep $record): bool => $record?->type === 'comment'),
            TextInput::make('planned_delay_minutes')
                ->label('Задержка, минут')
                ->numeric()
                ->minValue(0)
                ->maxValue(1440)
                ->required()
                ->helperText('Используется, только если точная дата не задана.'),
            DateTimePicker::make('scheduled_at')
                ->label('Точная дата публикации')
                ->seconds(false)
                ->native(false)
                ->helperText('Можно указать дату в прошлом: материал опубликуется сразу, но в сообществе получит эту дату.'),
            TextInput::make('purpose')->label('Роль в обсуждении')->maxLength(255)->columnSpanFull(),
            Select::make('conversation_move')
                ->label('Разговорный ход')
                ->options([
                    'question' => 'Уточняющий вопрос',
                    'agree' => 'Короткое согласие',
                    'disagree' => 'Короткое возражение',
                    'clarify' => 'Уточнение условия',
                    'correct' => 'Мягкая поправка',
                    'doubt' => 'Сомнение без решения',
                    'practical_detail' => 'Одна практическая деталь',
                    'support' => 'Короткая поддержка',
                    'light_humor' => 'Лёгкая ирония',
                    'partial_answer' => 'Частичный ответ',
                ])
                ->visible(fn (?CommunityAiScenarioStep $record): bool => $record?->type === 'comment'),
            TextInput::make('target_word_count')
                ->label('Ориентир длины, слов')
                ->numeric()
                ->minValue(5)
                ->maxValue(65)
                ->visible(fn (?CommunityAiScenarioStep $record): bool => $record?->type === 'comment'),
            TextInput::make('draft_title')->label('Заголовок')->maxLength(180)->visible(fn (?CommunityAiScenarioStep $record): bool => $record?->type === 'topic')->columnSpanFull(),
            Textarea::make('draft_body')->label('Текст')->rows(10)->required()->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sequence')
                    ->label('#')
                    ->sortable()
                    ->width('3rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 3rem; white-space: nowrap;']),
                TextColumn::make('persona.communityUser.display_name')
                    ->label('Персона')
                    ->description(fn (CommunityAiScenarioStep $record): string => '@'.$record->persona->communityUser->username)
                    ->width('10rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 10rem;']),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'topic' ? 'Тема' : 'Комментарий')
                    ->width('7rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 7rem; white-space: nowrap;']),
                TextColumn::make('parentStep.persona.communityUser.display_name')
                    ->label('Ответ на')
                    ->placeholder('Основная тема')
                    ->description(fn (CommunityAiScenarioStep $record): ?string => $record->parentStep
                        ? Str::limit((string) $record->parentStep->draft_body, 80)
                        : null)
                    ->wrap()
                    ->width('13rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 13rem; max-width: 16rem;']),
                TextColumn::make('draft_title')
                    ->label('Заголовок')
                    ->wrap()
                    ->placeholder('—')
                    ->width('18rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 18rem; max-width: 22rem;']),
                TextColumn::make('draft_body')
                    ->label('Текст')
                    ->wrap()
                    ->width('30rem')
                    ->verticallyAlignStart()
                    ->extraAttributes(['class' => 'whitespace-pre-wrap'])
                    ->extraCellAttributes(['style' => 'min-width: 30rem; max-width: 42rem;']),
                TextColumn::make('planned_delay_minutes')
                    ->label('Через')
                    ->suffix(' мин.')
                    ->width('5rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 5rem; white-space: nowrap;']),
                TextColumn::make('scheduled_at')
                    ->label('Точная дата')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('По задержке')
                    ->width('9rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 9rem; white-space: nowrap;']),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->width('9rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 9rem; white-space: nowrap;']),
                TextColumn::make('published_at')
                    ->label('Опубликован')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->width('9rem')
                    ->grow(false)
                    ->verticallyAlignStart()
                    ->extraCellAttributes(['style' => 'min-width: 9rem; white-space: nowrap;']),
            ])
            ->defaultSort('sequence')
            ->recordActions([
                ViewAction::make()
                    ->label('Открыть полностью')
                    ->iconButton()
                    ->tooltip('Открыть полный текст'),
                EditAction::make()->visible(fn (CommunityAiScenarioStep $record): bool => in_array($record->status, ['draft', 'pending_review', 'approved', 'failed'], true)
                    || ($record->status === 'scheduled' && $record->scenario->status === 'paused')),
                Action::make('approve_step')
                    ->label('Одобрить')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (CommunityAiScenarioStep $record): bool => $record->status === 'pending_review')
                    ->action(fn (CommunityAiScenarioStep $record) => $record->update(['status' => 'approved'])),
                Action::make('skip_step')
                    ->label('Исключить')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CommunityAiScenarioStep $record): bool => $record->type === 'comment' && in_array($record->status, ['pending_review', 'approved'], true))
                    ->action(fn (CommunityAiScenarioStep $record) => $record->update(['status' => 'skipped'])),
            ]);
    }
}
