<?php

namespace Database\Seeders;

use App\Models\SeoKeywordCluster;
use App\Models\SeoMonitoringSetting;
use App\Models\SeoResearchRun;
use App\Services\Seo\WordstatCsvImporter;
use Illuminate\Database\Seeder;

class SeoMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        SeoMonitoringSetting::instance();

        foreach ((array) config('seo-monitoring.seed_clusters') as $seed => $cluster) {
            SeoKeywordCluster::query()->updateOrCreate(
                ['slug' => $cluster['slug']],
                [
                    'name' => $cluster['name'],
                    'seed_phrase' => $seed,
                    'target_url' => filled($cluster['target'] ?? null) ? url($cluster['target']) : null,
                    'search_intent' => $cluster['intent'] ?? null,
                ],
            );
        }

        $path = database_path('data/seo-wordstat-2026-08-16.csv');

        if (! is_file($path)) {
            return;
        }

        $hash = hash_file('sha256', $path);
        $alreadyImported = SeoResearchRun::query()
            ->where('type', 'wordstat')
            ->get(['metadata'])
            ->contains(fn (SeoResearchRun $run): bool => ($run->metadata['sha256'] ?? null) === $hash);

        if (! $alreadyImported) {
            app(WordstatCsvImporter::class)->import($path);
        }
    }
}
