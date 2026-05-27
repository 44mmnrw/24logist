<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;

$url = app(GenerateSignedUploadUrl::class)->forLocal();
$tmp = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest',
    ],
    CURLOPT_POSTFIELDS => [
        'files' => [
            new CURLFile($tmp, 'image/png', 'test.png'),
        ],
    ],
]);
$body = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmp);

echo "HTTP {$code}\n{$body}\n";
