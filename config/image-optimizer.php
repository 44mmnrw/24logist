<?php

return [
    'enabled' => env('IMAGE_OPTIMIZER_ENABLED', true),
    'node_binary' => env('IMAGE_OPTIMIZER_NODE_BINARY', 'node'),
    'script' => base_path('script_ai/generate-image-variants.mjs'),
    'timeout' => (int) env('IMAGE_OPTIMIZER_TIMEOUT', 120),
    'webp_quality' => (int) env('IMAGE_OPTIMIZER_WEBP_QUALITY', 82),
    'avif_quality' => (int) env('IMAGE_OPTIMIZER_AVIF_QUALITY', 62),
    'widths' => [
        'hero' => [640, 1280],
        'mobile' => [320, 640],
        'blog_card' => [640, 1200],
        'blog_cover' => [640, 1280],
        'blog_body' => [480, 960, 1440],
    ],
];
