<?php

namespace App\Support;

final class LandingLinks
{
    public static function resolve(?string $link): ?string
    {
        $link = trim((string) ($link ?? ''));

        if ($link === '') {
            return null;
        }

        if (! str_starts_with($link, '#')) {
            return $link;
        }

        if (request()->is('/')) {
            return $link;
        }

        return url('/').$link;
    }
}
