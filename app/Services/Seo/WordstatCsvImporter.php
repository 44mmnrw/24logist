<?php

namespace App\Services\Seo;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordCluster;
use App\Models\SeoKeywordSnapshot;
use App\Models\SeoResearchRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class WordstatCsvImporter
{
    public function import(string $path): SeoResearchRun
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Wordstat CSV is not readable: {$path}");
        }

        $rows = $this->readRows($path);
        $run = SeoResearchRun::query()->create([
            'type' => 'wordstat',
            'source' => 'yandex_wordstat',
            'status' => 'running',
            'region_id' => (string) (config('seo-monitoring.default_region_id') ?: '225'),
            'device' => (string) (config('seo-monitoring.default_device') ?: 'DEVICE_ALL'),
            'total_items' => count($rows),
            'started_at' => now(),
            'metadata' => ['file' => basename($path), 'sha256' => hash_file('sha256', $path)],
        ]);

        try {
            DB::transaction(function () use ($rows, $run): void {
                foreach ($rows as $row) {
                    $this->importRow($row, $run);
                    $run->increment('processed_items');
                }
            });

            $run->update(['status' => 'completed', 'finished_at' => now()]);
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error' => mb_substr($exception->getMessage(), 0, 5000),
            ]);

            throw $exception;
        }

        return $run->refresh();
    }

    /** @return list<array<string, string>> */
    private function readRows(string $path): array
    {
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException("Cannot open Wordstat CSV: {$path}");
        }

        $header = fgetcsv($stream, separator: ';', enclosure: '"', escape: '');

        if (! is_array($header)) {
            fclose($stream);
            throw new RuntimeException('Wordstat CSV has no header.');
        }

        $header[0] = ltrim((string) $header[0], "\xEF\xBB\xBF");
        $rows = [];

        while (($values = fgetcsv($stream, separator: ';', enclosure: '"', escape: '')) !== false) {
            if (count($values) !== count($header)) {
                continue;
            }

            $row = array_combine($header, $values);

            if (is_array($row) && filled($row['phrase'] ?? null)) {
                $rows[] = array_map(static fn ($value): string => (string) $value, $row);
            }
        }

        fclose($stream);

        return $rows;
    }

    /** @param array<string, string> $row */
    private function importRow(array $row, SeoResearchRun $run): void
    {
        $regionId = trim($row['regions'] ?? '') ?: $run->region_id;
        $device = strtoupper(trim($row['device'] ?? '')) ?: $run->device;
        $phrase = trim($row['phrase']);
        $normalized = SeoKeyword::normalizePhrase($phrase);
        $seed = SeoKeyword::normalizePhrase(explode(' | ', $row['seeds'] ?? '')[0] ?? '');
        $cluster = $this->resolveCluster($seed);
        $recordedAt = filled($row['collected_at'] ?? null)
            ? Carbon::parse($row['collected_at'])
            : now();
        $count = max(0, (int) ($row['count'] ?? 0));
        $sourceType = trim($row['source'] ?? '');

        $keyword = SeoKeyword::query()->updateOrCreate(
            ['identity_hash' => SeoKeyword::identityHash($phrase, $regionId, $device)],
            [
                'seo_keyword_cluster_id' => $cluster?->getKey(),
                'phrase' => $phrase,
                'normalized_phrase' => $normalized,
                'region_id' => $regionId,
                'device' => $device,
                'source_type' => $sourceType,
                'target_url' => $cluster?->target_url,
                'is_active' => str_contains($sourceType, 'result'),
                'latest_wordstat_count' => $count,
                'wordstat_updated_at' => $recordedAt,
            ],
        );

        SeoKeywordSnapshot::query()->updateOrCreate(
            ['seo_keyword_id' => $keyword->getKey(), 'seo_research_run_id' => $run->getKey()],
            [
                'source' => 'yandex_wordstat',
                'wordstat_count' => $count,
                'recorded_at' => $recordedAt,
                'raw' => [
                    'source' => $sourceType,
                    'seeds' => $row['seeds'] ?? null,
                ],
            ],
        );
    }

    private function resolveCluster(string $seed): ?SeoKeywordCluster
    {
        if ($seed === '') {
            return null;
        }

        $settings = config('seo-monitoring.seed_clusters.'.$seed, []);
        $name = (string) ($settings['name'] ?? Str::headline($seed));
        $slug = (string) ($settings['slug'] ?? Str::slug($seed));
        $target = filled($settings['target'] ?? null) ? url((string) $settings['target']) : null;

        return SeoKeywordCluster::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'target_url' => $target,
                'search_intent' => $settings['intent'] ?? null,
            ],
        );
    }
}
