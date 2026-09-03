<?php

namespace Tests\Feature;

use App\Services\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EpdGameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_game_page_is_available_and_indexable(): void
    {
        $response = $this->get(route('epd-game'));

        $response
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie')
            ->assertHeader('Cache-Control', 'max-age=300, public, s-maxage=300')
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">', false)
            ->assertSee('data-epd-game', false)
            ->assertSee('data-epd-operator', false)
            ->assertSee('data-epd-target-operator', false)
            ->assertSee('data-epd-route', false)
            ->assertSee('data-epd-reel', false)
            ->assertSee('images/icons/epd-slot-frame.svg', false)
            ->assertSee('data-epd-core-state', false)
            ->assertSee('data-epd-result', false)
            ->assertSee('data-epd-outcome-dialog', false)
            ->assertSee('data-epd-result-sender', false)
            ->assertSee('data-epd-route-animation', false)
            ->assertSee('data-epd-route-failure-animation', false)
            ->assertSee('data-epd-route-success-animation', false)
            ->assertSee('data-epd-retry-animation', false)
            ->assertSee('data-epd-sound', false)
            ->assertSee('href="'.url('/').'"', false)
            ->assertSee('<h1>Рулетка роуминга ЭПД</h1>', false)
            ->assertSee('Испытать удачу')
            ->assertSee('ПФ СКБ Контур')
            ->assertSee('НТЦ СТЭК');

        $this->assertSame(34, substr_count($response->getContent(), '<option'));
    }

    public function test_game_page_is_listed_in_sitemap(): void
    {
        app(SitemapService::class)->clearCache();

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee('<loc>'.route('epd-game').'</loc>', false);
    }

    public function test_game_page_exposes_complete_link_preview_metadata(): void
    {
        $response = $this->get(route('epd-game'));

        $response
            ->assertOk()
            ->assertSee('<meta property="og:title" content="Рулетка роуминга ЭПД">', false)
            ->assertSee('<meta property="og:locale" content="ru_RU">', false)
            ->assertSee('<meta property="og:url" content="'.route('epd-game').'">', false)
            ->assertSee('<meta property="og:image:url" content="'.asset('images/epd-game-og.png').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<link rel="canonical" href="'.route('epd-game').'">', false)
            ->assertSee('<script type="application/ld+json">', false)
            ->assertSee('"@type":"WebApplication"', false)
            ->assertSee('images/epd-game-og.png', false)
            ->assertSee('<meta property="og:image:type" content="image/png">', false);
    }
}
