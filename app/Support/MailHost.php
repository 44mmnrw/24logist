<?php

namespace App\Support;

final class MailHost
{
    public static function normalize(?string $host): ?string
    {
        if ($host === null) {
            return null;
        }

        $host = trim($host);

        if ($host === '') {
            return null;
        }

        $host = preg_replace('#^(ssl|tls)://#i', '', $host) ?? $host;
        $host = rtrim($host, '/');

        if (preg_match('#^([^/:]+):\d+$#', $host, $matches)) {
            $host = $matches[1];
        }

        if (str_contains($host, '/')) {
            $host = (string) parse_url('//'.$host, PHP_URL_HOST);
        }

        return strtolower($host);
    }

    public static function looksLikeWebsiteHost(string $mailHost, ?string $appHost = null): bool
    {
        $mailHost = self::normalize($mailHost);
        $appHost = self::normalize($appHost ?? (string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($mailHost === null || $appHost === null) {
            return false;
        }

        return $mailHost === $appHost;
    }
}
