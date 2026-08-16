<?php

namespace App\Filament\Clusters\Landing\Resources\SeoKeywordClusters\Pages;

use App\Filament\Clusters\Landing\Resources\SeoKeywordClusters\SeoKeywordClusterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeoKeywordClusters extends ListRecords
{
    protected static string $resource = SeoKeywordClusterResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
