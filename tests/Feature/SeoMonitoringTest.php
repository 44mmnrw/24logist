<?php

namespace Tests\Feature;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordCluster;
use App\Models\SeoKeywordSnapshot;
use App\Models\SeoMonitoringSetting;
use App\Models\SeoResearchRun;
use App\Models\User;
use App\Services\Seo\KeywordRelevanceFilter;
use App\Services\Seo\WordstatCsvImporter;
use App\Services\Seo\YandexPositionChecker;
use App\Services\Seo\YandexWordstatCollector;
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
        $this->assertSame(2, SeoKeywordSnapshot::query()->where('seo_research_run_id', $run->id)->count());

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

    public function test_wordstat_can_be_updated_directly_from_api(): void
    {
        SeoMonitoringSetting::instance()->update(['yandex_api_key' => 'test-key', 'wordstat_limit' => 25]);
        SeoKeywordCluster::query()->create([
            'name' => 'ЭПД',
            'slug' => 'epd',
            'seed_phrase' => 'электронные перевозочные документы',
            'target_url' => 'https://24logist.ru/tag/epd',
        ]);
        Http::fake([
            'searchapi.api.cloud.yandex.net/v2/wordstat/topRequests' => Http::response([
                'results' => [['phrase' => 'электронные перевозочные документы', 'count' => 21451]],
                'associations' => [['phrase' => 'транспортный эдо', 'count' => 24659]],
            ]),
        ]);

        $run = app(YandexWordstatCollector::class)->collect();

        $this->assertSame('completed', $run->status);
        $this->assertDatabaseHas('seo_keywords', [
            'phrase' => 'электронные перевозочные документы',
            'latest_wordstat_count' => 21451,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('seo_keywords', ['phrase' => 'транспортный эдо', 'is_active' => false]);
        $this->assertSame(2, SeoKeywordSnapshot::query()->where('seo_research_run_id', $run->id)->count());
    }

    public function test_sorm_cluster_keeps_only_queries_related_to_freight_forwarders(): void
    {
        SeoMonitoringSetting::instance()->update(['yandex_api_key' => 'test-key', 'wordstat_limit' => 25]);
        $cluster = SeoKeywordCluster::query()->where('slug', 'sorm')->firstOrFail();
        $cluster->update(['seed_phrase' => 'сорм для экспедиторов']);

        Http::fake([
            'searchapi.api.cloud.yandex.net/v2/wordstat/topRequests' => Http::response([
                'results' => [
                    ['phrase' => 'сорм для экспедиторов цена', 'count' => 18],
                    ['phrase' => 'сорм для операторов связи', 'count' => 5000],
                ],
                'associations' => [
                    ['phrase' => 'пэк отслеживание груза', 'count' => 12000],
                    ['phrase' => 'сорм тэд стоимость', 'count' => 8],
                ],
            ]),
        ]);

        $run = app(YandexWordstatCollector::class)->collect();

        $this->assertDatabaseHas('seo_keywords', ['phrase' => 'сорм для экспедиторов цена']);
        $this->assertDatabaseHas('seo_keywords', ['phrase' => 'сорм тэд стоимость']);
        $this->assertDatabaseMissing('seo_keywords', ['phrase' => 'сорм для операторов связи']);
        $this->assertDatabaseMissing('seo_keywords', ['phrase' => 'пэк отслеживание груза']);
        $this->assertSame(2, SeoKeywordSnapshot::query()->where('seo_research_run_id', $run->id)->count());
    }

    public function test_semantic_core_rejects_generic_queries(): void
    {
        $filter = app(KeywordRelevanceFilter::class);
        $etrn = SeoKeywordCluster::query()->create(['name' => 'ЭТрН', 'slug' => 'etrn']);
        $software = SeoKeywordCluster::query()->create(['name' => 'Программа для экспедитора', 'slug' => 'programma-dlia-ekspeditora']);
        $logistics = SeoKeywordCluster::query()->create(['name' => 'Логистика', 'slug' => 'logistika', 'is_active' => false]);

        $this->assertTrue($filter->matches($etrn, 'электронные транспортные накладные для перевозчика'));
        $this->assertFalse($filter->matches($etrn, 'накладная'));
        $this->assertTrue($filter->matches($software, 'программа учета для экспедитора'));
        $this->assertFalse($filter->matches($software, 'экспедитор это'));
        $this->assertFalse($filter->matches($logistics, 'перевозка'));
        $this->assertFalse($logistics->is_active);
    }

    public function test_admin_can_open_all_seo_monitoring_tables(): void
    {
        $this->actingAs(User::factory()->create());

        foreach ([
            '/admin/seo/seo-keyword-clusters',
            '/admin/seo/seo-keywords',
            '/admin/seo/seo-keyword-snapshots',
            '/admin/seo/seo-research-runs',
            '/admin/seo/settings',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
