<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections\Pages;

use App\Filament\Clusters\Landing\Resources\LandingSections\LandingSectionResource;
use App\Services\LandingPageService;
use Filament\Resources\Pages\EditRecord;

class EditLandingSection extends EditRecord
{
    protected static string $resource = LandingSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function afterSave(): void
    {
        app(LandingPageService::class)->clearCache();
    }
}
