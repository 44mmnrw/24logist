<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

$base = config('app.url');
$request = Request::create($base.'/admin', 'GET', server: [
    'HTTP_HOST' => parse_url($base, PHP_URL_HOST),
    'HTTPS' => 'on',
    'SERVER_PORT' => '443',
]);
$app->instance('request', $request);
URL::forceRootUrl($base);

$src = __DIR__.'/../storage/app/public/landing/hero/01KSM5JX0SQ4PC56JWYFB8K43F.jpeg';
if (! is_file($src)) {
    fwrite(STDERR, "fixture missing\n");
    exit(1);
}

$tmp = tempnam(sys_get_temp_dir(), 'lw');
copy($src, $tmp);

$uploadUrl = app(GenerateSignedUploadUrl::class)->forLocal();
echo "POST {$uploadUrl}\n";

$session = $app->make('session')->driver();
$session->start();

$ch = curl_init($uploadUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'X-CSRF-TOKEN: '.$session->token(),
        'X-Requested-With: XMLHttpRequest',
    ],
    CURLOPT_POSTFIELDS => [
        'files' => new CURLFile($tmp, 'image/jpeg', 'curl-test.jpeg'),
    ],
    CURLOPT_COOKIE => $session->getName().'='.$session->getId(),
]);
$body = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmp);

echo "HTTP {$code}\n";
echo $body."\n";

if ($code !== 200) {
    exit(1);
}

$paths = json_decode((string) $body, true)['paths'] ?? [];
foreach ($paths as $signed) {
    $plain = TemporaryUploadedFile::extractPathFromSignedPath($signed);
    $storagePath = FileUploadConfiguration::path($plain);
    $disk = FileUploadConfiguration::disk();
    $exists = Storage::disk($disk)->exists($storagePath);
    echo "stored: disk={$disk} path={$storagePath} exists=".($exists ? 'yes size='.Storage::disk($disk)->size($storagePath) : 'NO')."\n";
    if ($exists) {
        Storage::disk($disk)->delete($storagePath);
        Storage::disk($disk)->delete(FileUploadConfiguration::path($plain.'.json'));
    }
}

echo "OK\n";
