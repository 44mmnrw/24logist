<?php

namespace App\Filament\Resources\CommunityAiScenarios\Pages;

use App\Filament\Resources\CommunityAiScenarios\CommunityAiScenarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityAiScenarios extends ListRecords
{
    protected static string $resource = CommunityAiScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Создать сценарий')];
    }
}
