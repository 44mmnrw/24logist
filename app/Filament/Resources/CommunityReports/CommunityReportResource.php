<?php

namespace App\Filament\Resources\CommunityReports;

use App\Filament\Resources\CommunityReports\Pages\EditCommunityReport;
use App\Filament\Resources\CommunityReports\Pages\ListCommunityReports;
use App\Models\CommunityReport;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunityReportResource extends Resource
{
    protected static ?string $model = CommunityReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Жалобы';

    protected static ?string $modelLabel = 'жалоба';

    protected static ?string $pluralModelLabel = 'Жалобы сообщества';

    protected static string|\UnitEnum|null $navigationGroup = 'Сообщество';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->label('Статус')->options(['open' => 'Открыта', 'actioned' => 'Приняты меры', 'dismissed' => 'Отклонена'])->required(),
            Textarea::make('details')->label('Комментарий пользователя')->disabled()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('target_type')->label('Объект')->badge(),
            TextColumn::make('target_id')->label('ID'),
            TextColumn::make('reason')->label('Причина')->badge(),
            TextColumn::make('details')->label('Комментарий')->limit(70),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('created_at')->label('Создана')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([SelectFilter::make('status')->options(['open' => 'Открыта', 'actioned' => 'Приняты меры', 'dismissed' => 'Отклонена'])])->defaultSort('created_at', 'desc')->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommunityReports::route('/'), 'edit' => EditCommunityReport::route('/{record}/edit')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
