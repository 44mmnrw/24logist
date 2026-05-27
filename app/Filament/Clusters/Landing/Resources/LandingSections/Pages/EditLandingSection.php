<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections\Pages;

use App\Filament\Clusters\Landing\Resources\LandingSections\LandingSectionResource;
use App\Services\LandingPageService;
use App\Support\LandingHeroCarouselForm;
use Filament\Resources\Pages\EditRecord;

class EditLandingSection extends EditRecord
{
    protected static string $resource = LandingSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return LandingHeroCarouselForm::hydrate($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LandingHeroCarouselForm::dehydrate($data);
    }

    protected function afterSave(): void
    {
        app(LandingPageService::class)->clearCache();
    }
}
