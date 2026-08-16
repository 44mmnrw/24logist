<?php

use App\Models\SeoKeyword;
use App\Models\SeoKeywordCluster;
use App\Models\SeoKeywordSnapshot;
use App\Models\SeoResearchRun;
use App\Services\Seo\WordstatCsvImporter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private string $filename = 'seo-wordstat-sorm-expeditors-2026-08-16.csv';

    public function up(): void
    {
        SeoKeywordCluster::query()->updateOrCreate(
            ['slug' => 'sorm'],
            [
                'name' => 'СОРМ',
                'seed_phrase' => 'сорм для экспедиторов',
                'description' => 'СОРМ для транспортно-экспедиционных компаний: требования, подключение и стоимость.',
                'target_url' => url('/tag/sorm'),
                'search_intent' => 'commercial',
                'is_active' => true,
            ],
        );

        $path = database_path('data/'.$this->filename);
        $hash = hash_file('sha256', $path);
        $alreadyImported = SeoResearchRun::query()
            ->where('type', 'wordstat')
            ->get(['metadata'])
            ->contains(fn (SeoResearchRun $run): bool => ($run->metadata['sha256'] ?? null) === $hash);

        if (! $alreadyImported) {
            app(WordstatCsvImporter::class)->import($path);
        }
    }

    public function down(): void
    {
        $path = database_path('data/'.$this->filename);

        if (! is_file($path)) {
            return;
        }

        $hash = hash_file('sha256', $path);
        $runs = SeoResearchRun::query()
            ->where('type', 'wordstat')
            ->get()
            ->filter(fn (SeoResearchRun $run): bool => ($run->metadata['sha256'] ?? null) === $hash);

        foreach ($runs as $run) {
            $keywordIds = SeoKeywordSnapshot::query()
                ->where('seo_research_run_id', $run->getKey())
                ->pluck('seo_keyword_id');

            SeoKeywordSnapshot::query()->where('seo_research_run_id', $run->getKey())->delete();
            $run->delete();

            SeoKeyword::query()
                ->whereKey($keywordIds)
                ->whereDoesntHave('snapshots')
                ->delete();
        }
    }
};
