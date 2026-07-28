<?php

namespace App\Http\Middleware;

use App\Services\PublicPageCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CachePublicLandingPage
{
    public function __construct(
        private readonly PublicPageCache $cache,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('public-cache.enabled', true) || ! $request->isMethodCacheable()) {
            return $next($request);
        }

        $cached = $this->cache->landing();

        if ($cached !== null) {
            $response = response($cached['content'], Response::HTTP_OK, [
                'Content-Type' => $cached['content_type'],
                'X-Public-Cache' => 'HIT',
            ]);

            return $this->prepareResponse($request, $response);
        }

        $response = $next($request);

        if ($this->isCacheable($response)) {
            $this->cache->putLanding(
                $response->getContent(),
                $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
            );
            $response->headers->set('X-Public-Cache', 'MISS');
        } else {
            $response->headers->set('Cache-Control', 'no-store');

            return $response;
        }

        return $this->prepareResponse($request, $response);
    }

    private function isCacheable(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return $response->isSuccessful()
            && str_starts_with($contentType, 'text/html')
            && ! $response->headers->has('Set-Cookie');
    }

    private function prepareResponse(Request $request, Response $response): Response
    {
        $response->setPublic();
        $response->setMaxAge(max(0, (int) config('public-cache.browser_ttl', 60)));
        $response->setSharedMaxAge(max(0, (int) config('public-cache.shared_ttl', 300)));
        $response->headers->addCacheControlDirective(
            'stale-while-revalidate',
            max(0, (int) config('public-cache.stale_while_revalidate', 60)),
        );
        $response->headers->addCacheControlDirective(
            'stale-if-error',
            max(0, (int) config('public-cache.stale_if_error', 86400)),
        );
        $response->setEtag(hash('sha256', $response->getContent()));
        $response->setVary('Accept-Encoding');
        $response->isNotModified($request);

        return $response;
    }
}
