<?php

namespace App\Filament\Clusters\Landing\Resources\CmsPages\Pages;

use App\Filament\Clusters\Landing\Resources\CmsPages\CmsPageResource;
use App\Services\CmsPageService;
use App\Services\SitemapService;
use App\Support\FilamentMediaUpload;
use Filament\Resources\Pages\EditRecord;

class EditCmsPage extends EditRecord
{
    protected static string $resource = CmsPageResource::class;

    protected ?string $originalSlug = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $extra = FilamentMediaUpload::wrapExtraPathForFill($extra, 'og_image_path');
        $data['extra'] = $extra;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalSlug ??= $this->record->slug;

        $extra = is_array($data['extra'] ?? null) ? $data['extra'] : [];
        $extra['og_image_path'] = FilamentMediaUpload::persist($extra['og_image_path'] ?? null, 'site/og/pages');
        $data['extra'] = $extra;

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
