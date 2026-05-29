<?php

namespace App\Filament\Clusters\Landing\Resources\LandingLeads\Pages;

use App\Filament\Clusters\Landing\Resources\LandingLeads\LandingLeadResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingLeads extends ListRecords
{
    protected static string $resource = LandingLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
