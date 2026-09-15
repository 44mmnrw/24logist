<?php

namespace App\Filament\Resources\CommunityAiSources;

use App\Filament\Resources\CommunityAiSources\Pages\CreateCommunityAiSource;
use App\Filament\Resources\CommunityAiSources\Pages\EditCommunityAiSource;
use App\Filament\Resources\CommunityAiSources\Pages\ListCommunityAiSources;
use App\Models\CommunityAiSource;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommunityAiSourceResource extends Resource
{
    protected static ?string $model = CommunityAiSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Источники AI';

    protected static ?string $modelLabel = 'источник AI';

    protected static ?string $pluralModelLabel = 'Источники для AI-сценариев';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Название')->required()->maxLength(120),
            Select::make('platform')->label('Платформа')->options(['max' => 'MAX'])->default('max')->required(),
            TextInput::make('external_chat_id')
                ->label('Chat ID MAX')
                ->helperText('ID берётся из адреса web.max.ru/-123…. Сообщения загружает расширение из авторизованного браузера.')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            TextInput::make('public_url')->label('Ссылка на чат')->url()->maxLength(2048),
            Toggle::make('is_active')->label('Источник активен')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('external_chat_id')->label('Chat ID')->copyable(),
                TextColumn::make('messages_count')->label('Сообщений')->counts('messages')->sortable(),
                IconColumn::make('is_active')->label('Активен')->boolean(),
                TextColumn::make('last_synced_at')->label('Последний импорт')->dateTime('d.m.Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('last_error')->label('Ошибка')->limit(50)->color('danger')->toggleable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommunityAiSources::route('/'),
            'create' => CreateCommunityAiSource::route('/create'),
            'edit' => EditCommunityAiSource::route('/{record}/edit'),
        ];
    }
}
