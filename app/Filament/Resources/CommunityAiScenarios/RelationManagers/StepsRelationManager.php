<?php

namespace App\Filament\Resources\CommunityAiScenarios\RelationManagers;

use App\Models\CommunityAiPersona;
use App\Models\CommunityAiScenarioStep;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            TextInput::make('planned_delay_minutes')->label('Задержка, минут')->numeric()->minValue(0)->maxValue(1440)->required(),
            TextInput::make('purpose')->label('Роль в обсуждении')->maxLength(255)->columnSpanFull(),
            TextInput::make('draft_title')->label('Заголовок')->maxLength(180)->visible(fn (?CommunityAiScenarioStep $record): bool => $record?->type === 'topic')->columnSpanFull(),
            Textarea::make('draft_body')->label('Текст')->rows(10)->required()->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sequence')->label('#')->sortable(),
                TextColumn::make('persona.communityUser.display_name')->label('Персона')->description(fn (CommunityAiScenarioStep $record): string => '@'.$record->persona->communityUser->username),
                TextColumn::make('type')->label('Тип')->badge()->formatStateUsing(fn (string $state): string => $state === 'topic' ? 'Тема' : 'Комментарий'),
                TextColumn::make('draft_title')->label('Заголовок')->limit(45)->placeholder('—'),
                TextColumn::make('draft_body')->label('Текст')->limit(80)->wrap(),
                TextColumn::make('planned_delay_minutes')->label('Через')->suffix(' мин.'),
                TextColumn::make('status')->label('Статус')->badge(),
                TextColumn::make('published_at')->label('Опубликован')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->defaultSort('sequence')
            ->recordActions([
                EditAction::make()->visible(fn (CommunityAiScenarioStep $record): bool => in_array($record->status, ['draft', 'pending_review', 'approved', 'failed'], true)),
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
