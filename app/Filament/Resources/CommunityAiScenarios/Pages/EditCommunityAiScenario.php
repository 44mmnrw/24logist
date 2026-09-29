<?php

namespace App\Filament\Resources\CommunityAiScenarios\Pages;

use App\Filament\Resources\CommunityAiScenarios\CommunityAiScenarioResource;
use App\Models\CommunityAiScenario;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCommunityAiScenario extends EditRecord
{
    protected static string $resource = CommunityAiScenarioResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['scan_keywords'] = CommunityAiScenario::normalizeScanKeywords($data['scan_keywords'] ?? []);

        if (($data['mode'] ?? null) === CommunityAiScenario::MODE_MANUAL) {
            $data['scan_keywords'] = [];
        }

        return $data;
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return CommunityAiScenarioResource::scenarioActions();
    }
}
