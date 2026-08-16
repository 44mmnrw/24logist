<?php

use App\Models\SeoKeyword;
use App\Models\SeoKeywordCluster;
use App\Services\Seo\KeywordRelevanceFilter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $filter = app(KeywordRelevanceFilter::class);
        $trackedSlugs = array_keys((array) config('seo-monitoring.keyword_filters'));

        SeoKeywordCluster::query()->each(function (SeoKeywordCluster $cluster) use ($trackedSlugs): void {
            $cluster->update(['is_active' => in_array($cluster->slug, $trackedSlugs, true)]);
        });

        SeoKeyword::query()
            ->with('cluster')
            ->chunkById(200, function ($keywords) use ($filter): void {
                foreach ($keywords as $keyword) {
                    if (! $keyword->cluster || ! $keyword->cluster->is_active || ! $filter->matches($keyword->cluster, $keyword->phrase)) {
                        $keyword->update(['is_active' => false]);
                    }
                }
            });
    }

    public function down(): void
    {
        SeoKeywordCluster::query()->update(['is_active' => true]);
    }
};
