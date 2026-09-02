<?php

return [
    'telegram' => [
        'issuer' => 'https://oauth.telegram.org',
    ],
    'max' => [
        'init_data_ttl' => 3600,
        'challenge_ttl' => 300,
        'return_link_ttl' => 600,
    ],
    'limits' => [
        'post_title' => 180,
        'post_body' => 20000,
        'comment_body' => 5000,
        'comment_depth' => 6,
    ],
];
