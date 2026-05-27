<?php

namespace App\Filament\Clusters\Landing\Resources\LandingSections\Pages;

use App\Filament\Clusters\Landing\Resources\LandingSections\LandingSectionResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingSections extends ListRecords
{
    protected static string $resource = LandingSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
