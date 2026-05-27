<?php

namespace App\Filament\Clusters\Landing\Resources\LandingBlocks\Pages;

use App\Filament\Clusters\Landing\Resources\LandingBlocks\LandingBlockResource;
use App\Services\LandingPageService;
use Filament\Resources\Pages\CreateRecord;

class CreateLandingBlock extends CreateRecord
{
    protected static string $resource = LandingBlockResource::class;

    protected function afterCreate(): void
    {
        app(LandingPageService::class)->clearCache();
    }
}
