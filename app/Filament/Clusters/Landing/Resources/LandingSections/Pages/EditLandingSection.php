<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections\Pages;

use App\Filament\Clusters\Landing\Resources\LandingSections\LandingSectionResource;
use App\Services\LandingPageService;
use App\Support\LandingFooterForm;
use App\Support\LandingHeroCarouselForm;
use App\Support\LandingMobileForm;
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
        return LandingFooterForm::hydrate(
            LandingMobileForm::hydrate(
                LandingHeroCarouselForm::hydrate($data),
            ),
        );
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LandingFooterForm::dehydrate(
            LandingMobileForm::dehydrate(
                LandingHeroCarouselForm::dehydrate($data),
            ),
        );
    }

    protected function afterSave(): void
    {
        app(LandingPageService::class)->clearCache();
    }
}
