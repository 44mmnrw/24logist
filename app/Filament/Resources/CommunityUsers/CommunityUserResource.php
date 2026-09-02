<?php

namespace App\Filament\Resources\CommunityUsers;

use App\Filament\Resources\CommunityUsers\Pages\EditCommunityUser;
use App\Filament\Resources\CommunityUsers\Pages\ListCommunityUsers;
use App\Models\CommunityUser;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
            Select::make('transport_role')->label('Роль в перевозках')->options(CommunityUser::TRANSPORT_ROLES)->placeholder('Не указана'),
            Select::make('role')->label('Права в сообществе')->options(['user' => 'Участник', 'moderator' => 'Модератор'])->required(),
            Textarea::make('bio')->label('О себе')->rows(5)->maxLength(1000)->columnSpanFull(),
            TextInput::make('karma')->label('Рейтинг')->numeric()->disabled(),
            DateTimePicker::make('suspended_until')->label('Ограничен до'),
            DateTimePicker::make('banned_at')->label('Заблокирован с'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('avatar_path')->label('Аватар')->disk('public')->circular()->defaultImageUrl(null),
            TextColumn::make('display_name')->label('Никнейм')->searchable()->sortable(),
            TextColumn::make('username')->label('ID')->searchable()->sortable(),
            TextColumn::make('transport_role')->label('В перевозках')->formatStateUsing(fn (?string $state): string => CommunityUser::TRANSPORT_ROLES[$state] ?? 'Не указана'),
            TextColumn::make('role')->label('Права')->badge(),
            TextColumn::make('karma')->label('Рейтинг')->sortable(),
            TextColumn::make('posts_count')->label('Тем')->counts('posts'),
            TextColumn::make('comments_count')->label('Комментариев')->counts('comments'),
            TextColumn::make('suspended_until')->label('Ограничен до')->dateTime('d.m.Y H:i')->placeholder('—'),
            TextColumn::make('banned_at')->label('Блокировка')->dateTime('d.m.Y H:i')->placeholder('—'),
        ])->filters([SelectFilter::make('role')->options(['user' => 'Участник', 'moderator' => 'Модератор'])])->defaultSort('created_at', 'desc')->recordActions([EditAction::make()]);
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
