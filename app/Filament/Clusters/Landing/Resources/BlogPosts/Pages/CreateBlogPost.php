<?php

namespace App\Filament\Clusters\Landing\Resources\BlogPosts\Pages;

use App\Filament\Clusters\Landing\Resources\BlogPosts\BlogPostResource;
use App\Services\SitemapService;
use App\Support\FilamentMediaUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cover_image_path'] = FilamentMediaUpload::persist($data['cover_image_path'] ?? null, 'blog/covers');
        $data['og_image_path'] = FilamentMediaUpload::persist($data['og_image_path'] ?? null, 'blog/og');

        return $data;
    }

    protected function afterCreate(): void
    {
        app(SitemapService::class)->clearCache();
    }
}
