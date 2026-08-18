<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Support\LandingLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CmsPageLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config(['app.url' => 'https://24logist.ru']);
        URL::forceRootUrl('https://24logist.ru');
        URL::forceScheme('https');
    }

    public function test_cms_page_slug_is_normalized_from_pages_url(): void
    {
        $page = CmsPage::query()->create([
            'title' => 'Описание функциональных характеристик',
            'slug' => 'https://24logist.ru/pages/pages/pages/opisanie-funkcionalnyh-harakteristik',
            'is_published' => true,
        ]);

        $this->assertSame('opisanie-funkcionalnyh-harakteristik', $page->refresh()->slug);
        $this->assertSame(
            'https://24logist.ru/pages/opisanie-funkcionalnyh-harakteristik',
            $page->getUrl(),
        );
    }

    public function test_bare_cms_page_slug_resolves_to_pages_url(): void
    {
        CmsPage::query()->create([
            'title' => 'Описание функциональных характеристик',
            'slug' => 'opisanie-funkcionalnyh-harakteristik',
            'is_published' => true,
        ]);

        $this->assertSame(
            'https://24logist.ru/pages/opisanie-funkcionalnyh-harakteristik',
            LandingLinks::resolve('opisanie-funkcionalnyh-harakteristik'),
        );
    }

    public function test_internal_pages_url_is_normalized(): void
    {
        $this->assertSame(
            'https://24logist.ru/pages/opisanie-funkcionalnyh-harakteristik',
            LandingLinks::resolve('https://24logist.ru/pages/pages/opisanie-funkcionalnyh-harakteristik'),
        );
    }

    public function test_non_cms_links_are_not_rewritten(): void
    {
        $this->assertSame('/blog', LandingLinks::resolve('/blog'));
        $this->assertSame('mailto:info@24logist.ru', LandingLinks::resolve('mailto:info@24logist.ru'));
        $this->assertSame('https://example.com', LandingLinks::resolve('https://example.com'));
    }

    public function test_meta_title_and_open_graph_title_are_rendered_independently(): void
    {
        CmsPage::query()->create([
            'title' => 'Эксплуатационная документация',
            'slug' => 'ekspluatacionnaia-dokumentaciia-po-logistru',
            'meta_title' => 'Эксплуатационная документация программы для экспедиторов ЛогистРу',
            'body' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Документация"}]}]}',
            'extra' => [
                'og_title' => 'Эксплуатационная документация ПО ЛогистРу',
            ],
            'is_published' => true,
        ]);

        $this->get('/pages/ekspluatacionnaia-dokumentaciia-po-logistru')
            ->assertOk()
            ->assertSee(
                '<title>Эксплуатационная документация программы для экспедиторов ЛогистРу</title>',
                false,
            )
            ->assertSee(
                'property="og:title" content="Эксплуатационная документация ПО ЛогистРу"',
                false,
            );
    }
}
