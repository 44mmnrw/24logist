<?php

namespace App\Console\Commands;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordSnapshot;
use App\Models\SeoMonitoringSetting;
use App\Models\SeoResearchRun;
use App\Services\Seo\YandexPositionChecker;
use Illuminate\Console\Command;
use Throwable;

class CheckSeoPositions extends Command
{
    protected $signature = 'seo:check-positions {--limit=20 : Maximum keywords per run}';

    protected $description = 'Check 24logist.ru positions in Yandex Search API';

    public function handle(YandexPositionChecker $checker): int
    {
        $settings = SeoMonitoringSetting::instance();
        $limit = min(500, max(1, (int) $this->option('limit')));
        $keywords = SeoKeyword::query()
            ->where('is_active', true)
            ->orderByRaw('position_checked_at IS NOT NULL')
            ->orderBy('position_checked_at')
            ->orderByDesc('latest_wordstat_count')
            ->limit($limit)
            ->get();

        if ($keywords->isEmpty()) {
            $this->warn('No active keywords to check.');

            return self::SUCCESS;
        }

        $run = SeoResearchRun::query()->create([
            'type' => 'positions',
            'source' => 'yandex_search',
            'status' => 'running',
            'region_id' => $settings->default_region_id,
            'device' => $settings->default_device,
            'total_items' => $keywords->count(),
            'started_at' => now(),
            'metadata' => ['depth' => $settings->position_depth],
        ]);
        $errors = [];

        foreach ($keywords as $keyword) {
            try {
                $result = $checker->check($keyword->phrase, $keyword->region_id, $keyword->device);
                $recordedAt = now();

                $keyword->update([
                    'latest_position' => $result['position'],
                    'latest_result_url' => $result['url'],
                    'position_checked_at' => $recordedAt,
                ]);

                SeoKeywordSnapshot::query()->create([
                    'seo_keyword_id' => $keyword->getKey(),
                    'seo_research_run_id' => $run->getKey(),
                    'source' => 'yandex_search',
                    'position' => $result['position'],
                    'result_url' => $result['url'],
                    'recorded_at' => $recordedAt,
                    'raw' => ['results' => $result['results']],
                ]);

                $this->line($keyword->phrase.': '.($result['position'] ?? '> '.$settings->position_depth));
            } catch (Throwable $exception) {
                $errors[$keyword->getKey()] = $exception->getMessage();
                $this->error($keyword->phrase.': '.$exception->getMessage());
            }

            $run->increment('processed_items');
            usleep(300000);
        }

        $run->update([
            'status' => $errors === [] ? 'completed' : 'completed_with_errors',
            'finished_at' => now(),
            'metadata' => array_merge((array) $run->metadata, ['errors' => $errors]),
            'error' => $errors === [] ? null : count($errors).' keyword checks failed.',
        ]);

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
