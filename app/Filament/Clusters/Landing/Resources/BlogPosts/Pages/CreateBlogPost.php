<?php

namespace App\Filament\Clusters\Landing\Resources\BlogPosts\Pages;

use App\Filament\Clusters\Landing\Resources\BlogPosts\BlogPostResource;
use App\Services\BlogCardImageGenerator;
use App\Services\BlogImageOptimizer;
use App\Services\SitemapService;
use App\Support\FilamentMediaUpload;
use Filament\Resources\Pages\CreateRecord;

class CreateBlogPost extends CreateRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['cover_image_path'] = FilamentMediaUpload::persist($data['cover_image_path'] ?? null, 'blog/covers');
        $data['card_source_image_path'] = FilamentMediaUpload::persist($data['card_source_image_path'] ?? null, 'blog/cards');
        $data['og_image_path'] = FilamentMediaUpload::persist($data['og_image_path'] ?? null, 'blog/og');
        $data['twitter_image_path'] = FilamentMediaUpload::persist($data['twitter_image_path'] ?? null, 'blog/twitter');
        $data['schema_image_path'] = FilamentMediaUpload::persist($data['schema_image_path'] ?? null, 'blog/schema');

        return $data;
    }

    protected function afterCreate(): void
    {
        if (filled($this->record->card_source_image_path ?: $this->record->cover_image_path)) {
            app(BlogCardImageGenerator::class)->generate(
                $this->record,
                (bool) $this->record->show_card_logo,
            );
        }

        app(BlogImageOptimizer::class)->optimizePost($this->record->refresh());

        app(SitemapService::class)->clearCache();
    }
}
