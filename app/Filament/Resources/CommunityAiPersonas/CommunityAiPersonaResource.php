<?php

namespace App\Filament\Resources\CommunityAiPersonas;

use App\Filament\Resources\CommunityAiPersonas\Pages\EditCommunityAiPersona;
use App\Filament\Resources\CommunityAiPersonas\Pages\ListCommunityAiPersonas;
use App\Models\CommunityAiPersona;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommunityAiPersonaResource extends Resource
{
    protected static ?string $model = CommunityAiPersona::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Персонажи';

    protected static ?string $modelLabel = 'персонаж';

    protected static ?string $pluralModelLabel = 'Настройки персонажей';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Характер и голос')
                ->description('Эти настройки принадлежат платформе и передаются модели в системном сообщении при каждой генерации.')
                ->schema([
                    TextInput::make('role_description')
                        ->label('Роль персонажа')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Textarea::make('personality_description')
                        ->label('Характер')
                        ->helperText('Устойчивые черты, отношение к спорам, привычные реакции и то, чего персонаж не любит.')
                        ->required()
                        ->rows(6)
                        ->columnSpanFull(),
                    Textarea::make('settings.expertise')
                        ->label('Область знаний')
                        ->required()
                        ->rows(3),
                    Textarea::make('settings.viewpoint')
                        ->label('Позиция и угол зрения')
                        ->required()
                        ->rows(3),
                    Textarea::make('settings.communication_style')
                        ->label('Манера общения')
                        ->helperText('Темп, длина фраз, прямота, юмор, любимые и нежелательные обороты.')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('settings.custom_instructions')
                        ->label('Дополнительные индивидуальные инструкции')
                        ->helperText('Необязательно. Добавляются в конец системного промпта только этого персонажа.')
                        ->rows(5)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Грамотность и естественные неровности')
                ->description('Для каждой реплики платформа детерминированно выбирает обычный, разговорный или слегка небрежный режим.')
                ->schema([
                    Textarea::make('settings.literacy_profile.description')
                        ->label('Общий уровень грамотности')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('settings.literacy_profile.casual_chance')
                        ->label('Разговорный режим, %')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),
                    TextInput::make('settings.literacy_profile.error_chance')
                        ->label('Одна естественная ошибка, %')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->maxValue(60)
                        ->required(),
                    Textarea::make('settings.literacy_profile.imperfections')
                        ->label('Какие неровности допустимы')
                        ->helperText('Сумма двух вероятностей не должна превышать 100%. Ошибка не применяется к цифрам, реквизитам и терминам.')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Публикация')
                ->schema([
                    Toggle::make('is_active')->label('Персонаж активен'),
                    Toggle::make('requires_review')->label('Черновики требуют проверки'),
                    Toggle::make('can_create_posts')->label('Может создавать темы'),
                    Toggle::make('can_create_comments')->label('Может писать комментарии'),
                    TextInput::make('daily_post_limit')->label('Тем в сутки')->numeric()->integer()->minValue(0)->maxValue(100)->required(),
                    TextInput::make('daily_comment_limit')->label('Комментариев в сутки')->numeric()->integer()->minValue(0)->maxValue(500)->required(),
                    TextInput::make('max_post_tokens')->label('Лимит токенов темы')->numeric()->integer()->minValue(100)->maxValue(4000)->required(),
                    TextInput::make('max_comment_tokens')->label('Лимит токенов комментария')->numeric()->integer()->minValue(100)->maxValue(4000)->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make('Подключение Timeweb')
                ->description('Модель выбирается в настройках самого агента Timeweb. Платформа не переопределяет её в API-запросе.')
                ->schema([
                    TextInput::make('provider_agent_id')
                        ->label('ID агента')
                        ->required()
                        ->rules(['uuid'])
                        ->maxLength(36),
                    TextInput::make('provider_base_url')
                        ->label('API URL агента')
                        ->required()
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                    TextInput::make('_model_management')
                        ->label('Модель')
                        ->default('Выбирается и меняется в панели Timeweb')
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('communityUser.display_name')
                    ->label('Персонаж')
                    ->description(fn (CommunityAiPersona $record): string => '@'.$record->communityUser->username)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role_description')->label('Роль')->wrap()->searchable(),
                TextColumn::make('literacy')
                    ->label('Грамотность')
                    ->state(fn (CommunityAiPersona $record): string => (string) data_get($record->settings, 'literacy_profile.description', '—'))
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('error_chance')
                    ->label('Ошибки')
                    ->state(fn (CommunityAiPersona $record): string => data_get($record->settings, 'literacy_profile.error_chance', 0).'%'),
                IconColumn::make('is_active')->label('Активен')->boolean(),
                IconColumn::make('can_create_posts')->label('Темы')->boolean(),
                IconColumn::make('can_create_comments')->label('Комментарии')->boolean(),
                TextColumn::make('prompt_version')->label('Версия')->badge()->sortable(),
                TextColumn::make('last_acted_at')->label('Последняя активность')->dateTime('d.m.Y H:i')->placeholder('—')->sortable(),
            ])
            ->defaultSort('id')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityAiPersonas::route('/'),
            'edit' => EditCommunityAiPersona::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
