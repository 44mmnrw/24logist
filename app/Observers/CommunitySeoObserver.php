<?php

namespace App\Observers;

use App\Models\CommunityCategory;
use App\Models\CommunityPost;
use App\Models\CommunitySeoPage;
use App\Services\Community\CommunitySeoRegistry;
use App\Services\SitemapService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class CommunitySeoObserver
{
    public function saved(Model $source): void
    {
        $fields = match (true) {
            $source instanceof CommunityCategory => ['name', 'slug', 'is_active', 'description'],
            $source instanceof CommunityPost => ['title', 'slug', 'status', 'deleted_at', 'body_html', 'meta_robots', 'canonical_url'],
            default => [
                'username', 'display_name', 'first_name', 'last_name', 'show_first_name', 'show_last_name',
                'onboarded_at', 'terms_accepted_at', 'deleted_at',
            ],
        };
        if (! $source->wasRecentlyCreated && ! $source->wasChanged($fields)) {
            return;
        }
        // Earlier migrations also create community models on a fresh installation.
        if (Schema::hasTable('community_seo_pages')) {
            app(CommunitySeoRegistry::class)->syncSource($source);
            app(SitemapService::class)->clearCache();
        }
    }

    public function deleted(Model $source): void
    {
        if (! Schema::hasTable('community_seo_pages')) {
            return;
        }

        if (method_exists($source, 'isForceDeleting') && ! $source->isForceDeleting()) {
            app(CommunitySeoRegistry::class)->syncSource($source);

            return;
        }

        $kind = $source instanceof CommunityCategory ? 'category' : ($source instanceof CommunityPost ? 'post' : 'profile');
        CommunitySeoPage::query()->where('page_key', $kind.':'.$source->getKey())->get()->each->delete();
    }

    public function restored(Model $source): void
    {
        $this->saved($source);
    }
}
