<?php

namespace App\Filament\Clusters\Landing\Resources\CmsPages\Pages;

use App\Filament\Clusters\Landing\Resources\CmsPages\CmsPageResource;
use App\Services\CmsPageService;
use App\Services\SitemapService;
use Filament\Resources\Pages\EditRecord;

class EditCmsPage extends EditRecord
{
    protected static string $resource = CmsPageResource::class;

    protected ?string $originalSlug = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalSlug ??= $this->record->slug;

        return $data;
    }

    protected function afterSave(): void
    {
        app(CmsPageService::class)->clearCache($this->record, $this->originalSlug);
        app(SitemapService::class)->clearCache();
    }

    protected function afterDelete(): void
    {
        app(CmsPageService::class)->clearCache(null, $this->record->slug);
        app(SitemapService::class)->clearCache();
    }
}
