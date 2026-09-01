<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_public_page_contains_compact_cookie_notice(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-cookie-consent', false)
            ->assertSee('data-cookie-accept', false)
            ->assertSee('Мы используем')
            ->assertSee('Хорошо')
            ->assertSee('/pages/privacy-policy', false)
            ->assertDontSee('data-cookie-settings', false)
            ->assertDontSee('data-cookie-choice', false);
    }
}
