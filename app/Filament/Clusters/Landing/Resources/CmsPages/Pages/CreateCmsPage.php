<?php

namespace App\Filament\Clusters\Landing\Resources\CmsPages\Pages;

use App\Filament\Clusters\Landing\Resources\CmsPages\CmsPageResource;
use App\Services\CmsPageService;
use App\Services\SitemapService;
use App\Support\FilamentMediaUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateCmsPage extends CreateRecord
{
    protected static string $resource = CmsPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $extra['og_image_path'] = FilamentMediaUpload::persist($extra['og_image_path'] ?? null, 'site/og/pages');
        $data['extra'] = $extra;

        return $data;
    }

    protected function afterCreate(): void
    {
        app(CmsPageService::class)->clearCache($this->record);
        app(SitemapService::class)->clearCache();
    }
}
