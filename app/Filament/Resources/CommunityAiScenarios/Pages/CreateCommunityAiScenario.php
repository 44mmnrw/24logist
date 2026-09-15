<?php

namespace App\Filament\Resources\CommunityAiScenarios\Pages;

use App\Filament\Resources\CommunityAiScenarios\CommunityAiScenarioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityAiScenario extends CreateRecord
{
    protected static string $resource = CommunityAiScenarioResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
