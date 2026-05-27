<?php

namespace App\Filament\Clusters\Landing\Resources\LandingBlocks\Pages;

use App\Filament\Clusters\Landing\Resources\LandingBlocks\LandingBlockResource;
use App\Services\LandingPageService;
use Filament\Resources\Pages\EditRecord;

class EditLandingBlock extends EditRecord
{
    protected static string $resource = LandingBlockResource::class;

    protected function afterSave(): void
    {
        app(LandingPageService::class)->clearCache();
    }
}
