<?php

namespace App\Filament\Resources\CommunityAiScenarios\Pages;

use App\Filament\Resources\CommunityAiScenarios\CommunityAiScenarioResource;
use App\Models\CommunityAiScenario;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityAiScenario extends CreateRecord
{
    protected static string $resource = CommunityAiScenarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['mode'] ?? null) === CommunityAiScenario::MODE_MANUAL) {
            $data['source_ids'] = [];
            $data['source_from'] = now();
            $data['source_to'] = now();
            $data['scan_keywords'] = [];
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
