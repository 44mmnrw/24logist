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
}
