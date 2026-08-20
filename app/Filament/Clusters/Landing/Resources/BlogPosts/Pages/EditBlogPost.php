<?php

namespace App\Filament\Clusters\Landing\Resources\BlogPosts\Pages;

use App\Filament\Clusters\Landing\Resources\BlogPosts\BlogPostResource;
use App\Services\SitemapService;
use App\Support\FilamentMediaUpload;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = FilamentMediaUpload::wrapPathForFill($data, 'cover_image_path');
        $data = FilamentMediaUpload::wrapPathForFill($data, 'card_image_path');
        $data = FilamentMediaUpload::wrapPathForFill($data, 'og_image_path');
        $data = FilamentMediaUpload::wrapPathForFill($data, 'twitter_image_path');
        $data = FilamentMediaUpload::wrapPathForFill($data, 'schema_image_path');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['cover_image_path'] = FilamentMediaUpload::persist($data['cover_image_path'] ?? null, 'blog/covers');
        $data['card_image_path'] = FilamentMediaUpload::persist($data['card_image_path'] ?? null, 'blog/cards');
        $data['og_image_path'] = FilamentMediaUpload::persist($data['og_image_path'] ?? null, 'blog/og');
        $data['twitter_image_path'] = FilamentMediaUpload::persist($data['twitter_image_path'] ?? null, 'blog/twitter');
        $data['schema_image_path'] = FilamentMediaUpload::persist($data['schema_image_path'] ?? null, 'blog/schema');

        return $data;
    }

    protected function afterSave(): void
    {
        app(SitemapService::class)->clearCache();
    }

    protected function afterDelete(): void
    {
        app(SitemapService::class)->clearCache();
    }
}
