<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections\Pages;

use App\Filament\Clusters\Landing\Resources\LandingSections\LandingSectionResource;
use App\Services\LandingImageOptimizer;
use App\Services\LandingPageService;
use App\Services\SitemapService;
use App\Support\LandingFooterForm;
use App\Support\LandingFunctionalForm;
use App\Support\LandingHeroCarouselForm;
use App\Support\LandingMobileForm;
use App\Support\LandingPricingForm;
use App\Support\LandingQuizForm;
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
        return LandingFunctionalForm::hydrate(
            LandingQuizForm::hydrate(
                LandingPricingForm::hydrate(
                    LandingFooterForm::hydrate(
                        LandingMobileForm::hydrate(
                            LandingHeroCarouselForm::hydrate($data),
                        ),
                    ),
                ),
            ),
        );
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return LandingFunctionalForm::dehydrate(
            LandingQuizForm::dehydrate(
                LandingPricingForm::dehydrate(
                    LandingFooterForm::dehydrate(
                        LandingMobileForm::dehydrate(
                            LandingHeroCarouselForm::dehydrate($data),
                        ),
                    ),
                ),
            ),
        );
    }

    protected function afterSave(): void
    {
        app(LandingImageOptimizer::class)->optimizeSection($this->record);
        app(LandingPageService::class)->clearCache();
        app(SitemapService::class)->clearCache();
    }
}
