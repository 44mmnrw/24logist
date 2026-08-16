<?php

namespace Tests\Feature;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordCluster;
use App\Models\SeoKeywordSnapshot;
use App\Models\SeoResearchRun;
use App\Models\User;
use App\Services\Seo\WordstatCsvImporter;
use App\Services\Seo\YandexPositionChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SeoMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_wordstat_csv_is_imported_with_history_and_clusters(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'wordstat-');
        $csv = "\xEF\xBB\xBFphrase;count;source;seeds;regions;device;collected_at\r\n";
        $csv .= 'электронные перевозочные документы;21451;result;электронные перевозочные документы;225;DEVICE_ALL;2026-08-16T20:00:00+03:00'."\r\n";
        $csv .= 'транспортный эдо;24659;association;электронные перевозочные документы;225;DEVICE_ALL;2026-08-16T20:00:00+03:00'."\r\n";
        file_put_contents($path, $csv);

        $run = app(WordstatCsvImporter::class)->import($path);

        $this->assertSame('completed', $run->status);
        $this->assertSame(2, $run->processed_items);
        $this->assertDatabaseHas('seo_keyword_clusters', ['slug' => 'epd']);
        $this->assertDatabaseHas('seo_keywords', [
            'phrase' => 'электронные перевозочные документы',
            'latest_wordstat_count' => 21451,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('seo_keywords', [
            'phrase' => 'транспортный эдо',
            'is_active' => false,
        ]);
        $this->assertSame(2, SeoKeywordSnapshot::query()->count());

        @unlink($path);
    }

    public function test_yandex_position_checker_finds_target_domain(): void
    {
        config()->set('seo-monitoring.yandex_api_key', 'test-key');
        config()->set('seo-monitoring.target_host', '24logist.ru');
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<yandexsearch><response><results><grouping>
<group><doc><url>https://example.com/page</url></doc></group>
<group><doc><url>https://24logist.ru/tag/epd</url></doc></group>
</grouping></results></response></yandexsearch>
XML;

        Http::fake([
            'searchapi.api.cloud.yandex.net/*' => Http::response(['rawData' => base64_encode($xml)]),
        ]);

        $result = app(YandexPositionChecker::class)->check('электронные перевозочные документы');

        $this->assertSame(2, $result['position']);
        $this->assertSame('https://24logist.ru/tag/epd', $result['url']);
        $this->assertSame(2, $result['results']);
    }

    public function test_position_command_records_current_position_and_run(): void
    {
        config()->set('seo-monitoring.yandex_api_key', 'test-key');
        $cluster = SeoKeywordCluster::query()->create(['name' => 'ЭПД', 'slug' => 'epd']);
        SeoKeyword::query()->create([
            'seo_keyword_cluster_id' => $cluster->id,
            'phrase' => 'электронные перевозочные документы',
            'region_id' => '225',
            'device' => 'DEVICE_ALL',
            'is_active' => true,
        ]);
        $xml = '<yandexsearch><response><results><grouping><group><doc><url>https://24logist.ru/tag/epd</url></doc></group></grouping></results></response></yandexsearch>';
        Http::fake(['searchapi.api.cloud.yandex.net/*' => Http::response(['rawData' => base64_encode($xml)])]);

        $this->artisan('seo:check-positions', ['--limit' => 1])->assertSuccessful();

        $this->assertDatabaseHas('seo_keywords', ['latest_position' => 1]);
        $this->assertDatabaseHas('seo_keyword_snapshots', ['position' => 1, 'source' => 'yandex_search']);
        $this->assertDatabaseHas('seo_research_runs', ['type' => 'positions', 'status' => 'completed']);
        $this->assertSame(1, SeoResearchRun::query()->where('type', 'positions')->count());
    }

    public function test_admin_can_open_all_seo_monitoring_tables(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            '/admin/seo-keyword-clusters',
            '/admin/seo-keywords',
            '/admin/seo-keyword-snapshots',
            '/admin/seo-research-runs',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
