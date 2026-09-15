<?php

namespace App\Filament\Resources\CommunityAiSources\Pages;

use App\Filament\Resources\CommunityAiSources\CommunityAiSourceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityAiSources extends ListRecords
{
    protected static string $resource = CommunityAiSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Добавить чат MAX')];
    }
}
