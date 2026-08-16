<?php

namespace App\Services\Seo;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordCluster;
use App\Models\SeoKeywordSnapshot;
use App\Models\SeoMonitoringSetting;
use App\Models\SeoResearchRun;
use Illuminate\Http\Client\Factory;
use RuntimeException;
use Throwable;

class YandexWordstatCollector
{
    public function __construct(
        private readonly Factory $http,
        private readonly KeywordRelevanceFilter $relevanceFilter,
    ) {}

    public function collect(): SeoResearchRun
    {
        $settings = SeoMonitoringSetting::instance();
        $apiKey = trim((string) ($settings->yandex_api_key ?: config('seo-monitoring.yandex_api_key')));

        if ($apiKey === '') {
            throw new RuntimeException('API-ключ Yandex Search API не настроен.');
        }

        $clusters = SeoKeywordCluster::query()
            ->where('is_active', true)
            ->whereNotNull('seed_phrase')
            ->where('seed_phrase', '!=', '')
            ->orderBy('sort_order')
            ->get();

        if ($clusters->isEmpty()) {
            throw new RuntimeException('Нет активных кластеров с Seed-фразой Wordstat.');
        }

        $run = SeoResearchRun::query()->create([
            'type' => 'wordstat',
            'source' => 'yandex_wordstat_api',
            'status' => 'running',
            'region_id' => $settings->default_region_id,
            'device' => $settings->default_device,
            'total_items' => $clusters->count(),
            'started_at' => now(),
            'metadata' => ['limit' => $settings->wordstat_limit, 'clusters' => $clusters->pluck('seed_phrase')->all()],
        ]);
        $errors = [];

        foreach ($clusters as $cluster) {
            try {
                $this->collectCluster($cluster, $run, $settings, $apiKey);
            } catch (Throwable $exception) {
                $errors[$cluster->seed_phrase] = $exception->getMessage();
            }

            $run->increment('processed_items');
        }

        $run->update([
            'status' => $errors === [] ? 'completed' : 'completed_with_errors',
            'finished_at' => now(),
            'metadata' => array_merge((array) $run->metadata, ['errors' => $errors]),
            'error' => $errors === [] ? null : count($errors).' кластеров не обновлено.',
        ]);

        return $run->refresh();
    }

    private function collectCluster(
        SeoKeywordCluster $cluster,
        SeoResearchRun $run,
        SeoMonitoringSetting $settings,
        string $apiKey,
    ): void {
        $response = $this->http
            ->acceptJson()->asJson()
            ->withHeaders(['Authorization' => 'Api-Key '.$apiKey])
            ->connectTimeout(10)->timeout(60)->retry([500, 1500], throw: false)
            ->post('https://searchapi.api.cloud.yandex.net/v2/wordstat/topRequests', [
                'phrase' => $cluster->seed_phrase,
                'numPhrases' => min(2000, max(1, $settings->wordstat_limit)),
                'regions' => [$settings->default_region_id],
                'devices' => [$settings->default_device],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Yandex Wordstat API HTTP '.$response->status().': '.mb_substr($response->body(), 0, 1000));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Yandex Wordstat API вернул некорректный JSON.');
        }

        foreach (['result' => $data['results'] ?? [], 'association' => $data['associations'] ?? []] as $source => $items) {
            foreach ((array) $items as $item) {
                if (! is_array($item) || blank($item['phrase'] ?? null)) {
                    continue;
                }

                $phrase = trim((string) preg_replace('/\s+/u', ' ', (string) $item['phrase']));

                if (! $this->relevanceFilter->matches($cluster, $phrase)) {
                    continue;
                }

                $count = max(0, (int) ($item['count'] ?? 0));
                $identity = SeoKeyword::identityHash($phrase, $run->region_id, $run->device);
                $keyword = SeoKeyword::query()->firstOrNew(['identity_hash' => $identity]);
                $keyword->fill([
                    'seo_keyword_cluster_id' => $keyword->exists && $keyword->seo_keyword_cluster_id
                        ? $keyword->seo_keyword_cluster_id
                        : $cluster->getKey(),
                    'phrase' => $phrase,
                    'region_id' => $run->region_id,
                    'device' => $run->device,
                    'source_type' => $this->mergeSourceTypes($keyword->source_type, $source),
                    'target_url' => $keyword->target_url ?: $cluster->target_url,
                    'is_active' => $keyword->exists ? $keyword->is_active : $source === 'result',
                    'latest_wordstat_count' => $count,
                    'wordstat_updated_at' => now(),
                ])->save();

                SeoKeywordSnapshot::query()->updateOrCreate(
                    ['seo_keyword_id' => $keyword->getKey(), 'seo_research_run_id' => $run->getKey()],
                    [
                        'source' => 'yandex_wordstat',
                        'wordstat_count' => $count,
                        'recorded_at' => now(),
                        'raw' => ['source' => $source, 'seed' => $cluster->seed_phrase],
                    ],
                );
            }
        }
    }

    private function mergeSourceTypes(?string $current, string $source): string
    {
        $types = array_filter(explode(',', (string) $current));
        $types[] = $source;

        return implode(',', array_values(array_unique($types)));
    }
}
