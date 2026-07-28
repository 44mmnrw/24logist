<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

final class PublicPageCache
{
    private const LANDING_KEY = 'public-page-cache:landing:v1';

    /**
     * @return array{content: string, content_type: string}|null
     */
    public function landing(): ?array
    {
        $payload = $this->store()->get(self::LANDING_KEY);

        if (
            ! is_array($payload)
            || ! is_string($payload['content'] ?? null)
            || ! is_string($payload['content_type'] ?? null)
        ) {
            return null;
        }

        return $payload;
    }

    public function putLanding(string $content, string $contentType): void
    {
        $this->store()->put(
            self::LANDING_KEY,
            [
                'content' => $content,
                'content_type' => $contentType,
            ],
            max(1, (int) config('public-cache.landing_ttl', 300)),
        );
    }

    public function forgetLanding(): void
    {
        $this->store()->forget(self::LANDING_KEY);
    }

    private function store(): Repository
    {
        $store = config('public-cache.store');

        return Cache::store(is_string($store) && $store !== '' ? $store : null);
    }
}
