<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://24logist.ru']);
        config(['app.env' => 'production']);
    }

    public function test_www_host_redirects_to_canonical_host_with_301(): void
    {
        $response = $this->get('https://www.24logist.ru/pages/contacts', [
            'Host' => 'www.24logist.ru',
        ]);

        $response->assertRedirect('https://24logist.ru/pages/contacts');
        $response->assertStatus(301);
    }

    public function test_http_on_canonical_host_redirects_to_https(): void
    {
        $response = $this->get('http://24logist.ru/', [
            'Host' => '24logist.ru',
        ]);

        $response->assertRedirect('https://24logist.ru/');
        $response->assertStatus(301);
    }

    public function test_canonical_host_does_not_redirect(): void
    {
        $response = $this->get('https://24logist.ru/');

        $response->assertOk();
    }
}
