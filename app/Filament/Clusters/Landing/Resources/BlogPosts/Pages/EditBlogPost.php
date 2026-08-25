<?php

namespace App\Filament\Clusters\Landing\Resources\BlogPosts\Pages;

use App\Filament\Clusters\Landing\Resources\BlogPosts\BlogPostResource;
use App\Services\BlogCardImageGenerator;
use App\Services\BlogImageOptimizer;
use App\Services\SitemapService;
use App\Support\FilamentMediaUpload;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    private bool $shouldGenerateCardImage = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Закрытый предпросмотр')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (): string => $this->record->previewUrl())
                ->openUrlInNewTab()
                ->tooltip('Подписанная ссылка действует 7 дней'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = FilamentMediaUpload::wrapPathForFill($data, 'cover_image_path');
        $data = FilamentMediaUpload::wrapPathForFill($data, 'card_source_image_path');
        $data = FilamentMediaUpload::wrapPathForFill($data, 'og_image_path');
        $data = FilamentMediaUpload::wrapPathForFill($data, 'twitter_image_path');
        $data = FilamentMediaUpload::wrapPathForFill($data, 'schema_image_path');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['cover_image_path'] = FilamentMediaUpload::persist($data['cover_image_path'] ?? null, 'blog/covers');
        $data['card_source_image_path'] = FilamentMediaUpload::persist($data['card_source_image_path'] ?? null, 'blog/cards');
        $data['og_image_path'] = FilamentMediaUpload::persist($data['og_image_path'] ?? null, 'blog/og');
        $data['twitter_image_path'] = FilamentMediaUpload::persist($data['twitter_image_path'] ?? null, 'blog/twitter');
        $data['schema_image_path'] = FilamentMediaUpload::persist($data['schema_image_path'] ?? null, 'blog/schema');

        $coverChanged = $data['cover_image_path'] !== $this->record->cover_image_path;
        $sourceChanged = $data['card_source_image_path'] !== $this->record->card_source_image_path;
        $logoChanged = (bool) ($data['show_card_logo'] ?? false) !== (bool) $this->record->show_card_logo;
        $positionChanged = ($data['card_logo_position'] ?? 'top-left') !== $this->record->logoPosition();

        $this->shouldGenerateCardImage = filled($data['card_source_image_path'] ?: $data['cover_image_path'])
            && (blank($this->record->card_image_path)
                || $coverChanged
                || $sourceChanged
                || $logoChanged
                || $positionChanged);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->shouldGenerateCardImage) {
            app(BlogCardImageGenerator::class)->generate(
                $this->record,
                (bool) $this->record->show_card_logo,
            );
        }

        app(BlogImageOptimizer::class)->optimizePost($this->record->refresh());

        app(SitemapService::class)->clearCache();
    }

    protected function afterDelete(): void
    {
        app(SitemapService::class)->clearCache();
    }
}
