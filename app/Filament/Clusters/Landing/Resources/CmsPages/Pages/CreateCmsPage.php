<?php

namespace App\Filament\Clusters\Landing\Resources\CmsPages\Pages;

use App\Filament\Clusters\Landing\Resources\CmsPages\CmsPageResource;
use App\Services\CmsPageService;
use App\Services\SitemapService;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsPage extends CreateRecord
{
    protected static string $resource = CmsPageResource::class;

    protected function afterCreate(): void
    {
        app(CmsPageService::class)->clearCache($this->record);
        app(SitemapService::class)->clearCache();
    }
}
