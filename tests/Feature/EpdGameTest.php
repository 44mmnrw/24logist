<?php

namespace Tests\Feature;

use Tests\TestCase;

final class EpdGameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_game_page_is_available_only_by_its_direct_route(): void
    {
        $response = $this->get(route('epd-game'));

        $response
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('data-epd-game', false)
            ->assertSee('data-epd-operator', false)
            ->assertSee('data-epd-reel', false)
            ->assertSee('data-epd-result', false)
            ->assertSee('data-epd-route-animation', false)
            ->assertSee('data-epd-route-failure-animation', false)
            ->assertSee('data-epd-route-success-animation', false)
            ->assertSee('images/icons/epd-sender.svg', false)
            ->assertSee('data-epd-retry-animation', false)
            ->assertSee('data-epd-sound', false)
            ->assertSee('Установите связь ЭПД')
            ->assertSee('АО «ПФ «СКБ Контур»')
            ->assertSee('АО «НТЦ СТЭК»');

        $this->assertSame(17, substr_count($response->getContent(), '<option'));
    }
}
