<?php

return [
    'enabled' => env('PUBLIC_PAGE_CACHE_ENABLED', true),
    'store' => env('PUBLIC_PAGE_CACHE_STORE'),
    'landing_ttl' => (int) env('PUBLIC_PAGE_CACHE_LANDING_TTL', 300),
    'browser_ttl' => (int) env('PUBLIC_PAGE_CACHE_BROWSER_TTL', 60),
    'shared_ttl' => (int) env('PUBLIC_PAGE_CACHE_SHARED_TTL', 300),
    'stale_while_revalidate' => (int) env('PUBLIC_PAGE_CACHE_STALE_WHILE_REVALIDATE', 60),
    'stale_if_error' => (int) env('PUBLIC_PAGE_CACHE_STALE_IF_ERROR', 86400),
];
