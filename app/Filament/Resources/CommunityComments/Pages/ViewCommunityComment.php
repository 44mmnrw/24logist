<?php

namespace App\Filament\Resources\CommunityComments\Pages;

use App\Filament\Resources\CommunityComments\CommunityCommentResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityComment extends ViewRecord
{
    protected static string $resource = CommunityCommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Редактировать содержание'),
            ActionGroup::make(CommunityCommentResource::moderationActions())
                ->label('Модерация'),
        ];
    }
}
