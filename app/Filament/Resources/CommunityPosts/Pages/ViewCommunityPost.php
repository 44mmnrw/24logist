<?php

namespace App\Filament\Resources\CommunityPosts\Pages;

use App\Filament\Resources\CommunityPosts\CommunityPostResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityPost extends ViewRecord
{
    protected static string $resource = CommunityPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Редактировать содержание'),
            ActionGroup::make(CommunityPostResource::moderationActions())
                ->label('Модерация'),
        ];
    }
}
