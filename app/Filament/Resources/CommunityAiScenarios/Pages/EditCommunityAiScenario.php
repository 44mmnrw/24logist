<?php

namespace App\Filament\Resources\CommunityAiScenarios\Pages;

use App\Filament\Resources\CommunityAiScenarios\CommunityAiScenarioResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCommunityAiScenario extends EditRecord
{
    protected static string $resource = CommunityAiScenarioResource::class;

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return CommunityAiScenarioResource::scenarioActions();
    }
}
