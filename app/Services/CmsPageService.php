<?php

namespace App\Services;

use App\Models\CmsPage;
use Illuminate\Support\Facades\Cache;

class CmsPageService
{
    public function findPublished(string $slug): ?CmsPage
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        $pageId = Cache::remember(
            $this->cacheKey($slug),
            now()->addHour(),
            fn (): ?int => CmsPage::query()
                ->where('slug', $slug)
                ->where('is_published', true)
                ->value('id'),
        );

        if ($pageId === null) {
            return null;
        }

        return CmsPage::query()->find($pageId);
    }

    public function clearCache(?CmsPage $page = null, ?string $oldSlug = null): void
    {
        if ($page !== null) {
            Cache::forget($this->cacheKey($page->slug));
        }

        if ($oldSlug !== null && $oldSlug !== $page?->slug) {
            Cache::forget($this->cacheKey($oldSlug));
        }
    }

    private function cacheKey(string $slug): string
    {
        return 'cms.page.'.md5($slug);
    }
}
