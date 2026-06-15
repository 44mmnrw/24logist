<?php

namespace App\Support;

final class CanonicalUrl
{
    public static function root(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    public static function host(): ?string
    {
        $host = parse_url(self::root(), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }

    public static function scheme(): string
    {
        $scheme = parse_url(self::root(), PHP_URL_SCHEME);

        return is_string($scheme) && $scheme !== '' ? strtolower($scheme) : 'https';
    }

    public static function shouldEnforce(): bool
    {
        $host = self::host();

        if ($host === null) {
            return false;
        }

        return ! in_array($host, ['localhost', '127.0.0.1'], true)
            && ! str_ends_with($host, '.test')
            && ! str_ends_with($host, '.local');
    }

    public static function redirectTarget(string $requestUri): ?string
    {
        if (! self::shouldEnforce()) {
            return null;
        }

        $canonicalHost = self::host();
        $canonicalScheme = self::scheme();

        if ($canonicalHost === null) {
            return null;
        }

        $requestHost = strtolower(request()->getHost());
        $requestScheme = strtolower(request()->getScheme());

        if ($requestHost === $canonicalHost && $requestScheme === $canonicalScheme) {
            return null;
        }

        return self::root().$requestUri;
    }
}
