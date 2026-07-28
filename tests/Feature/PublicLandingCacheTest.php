<?php

namespace Tests\Feature;

use App\Services\PublicPageCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        app(PublicPageCache::class)->forgetLanding();
    }

    public function test_landing_is_session_free_and_publicly_cacheable(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertHeader('X-Public-Cache', 'MISS')
            ->assertSee('<meta name="csrf-token" content="">', false)
            ->assertSee('<meta name="csrf-endpoint" content="'.route('csrf.token').'">', false);

        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=60', $cacheControl);
        $this->assertStringContainsString('s-maxage=300', $cacheControl);
        $this->assertCount(0, $response->headers->getCookies());
        $this->assertNotEmpty($response->headers->get('ETag'));
    }

    public function test_landing_uses_cached_html_and_supports_conditional_requests(): void
    {
        $first = $this->get('/');
        $etag = (string) $first->headers->get('ETag');

        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Public-Cache', 'HIT')
            ->assertHeader('ETag', $etag);

        $this->withHeader('If-None-Match', $etag)
            ->get('/')
            ->assertStatus(304)
            ->assertHeader('X-Public-Cache', 'HIT');
    }

    public function test_landing_head_request_uses_the_same_public_cache_policy(): void
    {
        $this->get('/')->assertOk();

        $response = $this->head('/');

        $response
            ->assertOk()
            ->assertHeader('X-Public-Cache', 'HIT');

        $this->assertStringContainsString(
            'public',
            (string) $response->headers->get('Cache-Control'),
        );
        $this->assertNotEmpty($response->headers->get('ETag'));
        $this->assertCount(0, $response->headers->getCookies());
    }

    public function test_csrf_token_is_issued_only_on_demand_and_is_not_cacheable(): void
    {
        $response = $this->getJson(route('csrf.token'));

        $response
            ->assertOk()
            ->assertJsonStructure(['token']);

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control'),
        );
        $this->assertNotEmpty($response->json('token'));
        $this->assertNotEmpty($response->headers->getCookies());
    }
}
