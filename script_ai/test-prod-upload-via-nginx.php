<?php

declare(strict_types=1);

/**
 * POST upload through https://24logist.ru (nginx + php-fpm), like the browser.
 */
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

$tmp = tempnam(sys_get_temp_dir(), 'nginx');
file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

$url = app(\Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl::class)->forLocal();
echo "POST {$url}\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest',
    ],
    CURLOPT_POSTFIELDS => [
        'files' => new CURLFile($tmp, 'image/png', 'nginx-test.png'),
    ],
]);
$body = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmp);

echo "HTTP {$code}\n{$body}\n";

if ($code !== 200) {
    exit(1);
}

$paths = json_decode((string) $body, true)['paths'] ?? [];
foreach ($paths as $signed) {
    $plain = TemporaryUploadedFile::extractPathFromSignedPath($signed);
    $path = FileUploadConfiguration::path($plain);
    $disk = FileUploadConfiguration::disk();
    $exists = \Illuminate\Support\Facades\Storage::disk($disk)->exists($path);
    $size = $exists ? \Illuminate\Support\Facades\Storage::disk($disk)->size($path) : 0;
    echo "disk={$disk} path={$path} exists=".($exists ? "yes ({$size}b)" : 'NO')."\n";

    $preview = 'https://24logist.ru/storage/'.ltrim($path, '/');
    if ($disk === 'public') {
        $preview = 'https://24logist.ru/storage/livewire-tmp/'.$plain;
    }
    $previewCode = 0;
    $ch2 = curl_init($preview);
    curl_setopt_array($ch2, [CURLOPT_NOBODY => true, CURLOPT_RETURNTRANSFER => true]);
    curl_exec($ch2);
    $previewCode = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    echo "preview GET {$preview} => HTTP {$previewCode}\n";

    if ($exists) {
        \Illuminate\Support\Facades\Storage::disk($disk)->delete($path);
        \Illuminate\Support\Facades\Storage::disk($disk)->delete(FileUploadConfiguration::path($plain.'.json'));
    }
}

echo "OK: full nginx path\n";
