<?php

/**
 * Full HTTP kernel test: signed Livewire upload + session CSRF (like browser).
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

$baseRequest = Request::create(config('app.url'), 'GET');
$app->instance('request', $baseRequest);
URL::setRequest($baseRequest);
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

$src = __DIR__.'/../storage/app/public/landing/hero/01KSM5JX0SQ4PC56JWYFB8K43F.jpeg';
$tmp = tempnam(sys_get_temp_dir(), 'up');
file_put_contents($tmp, file_get_contents($src) ?: 'fail');
$size = filesize($tmp);
$file = new UploadedFile($tmp, 'prod-test.jpeg', 'image/jpeg', null, true);

$uploadUrl = app(GenerateSignedUploadUrl::class)->forLocal();
$parts = parse_url($uploadUrl);

$session = $app->make('session')->driver();
$session->start();
$token = $session->token();

$request = Request::create(
    ($parts['path'] ?? '').'?'.($parts['query'] ?? ''),
    'POST',
    [],
    $session->all(),
    ['files' => [$file]],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X-CSRF-TOKEN' => $token,
        'HTTP_X-Requested-With' => 'XMLHttpRequest',
        'HTTP_HOST' => parse_url(config('app.url'), PHP_URL_HOST),
        'HTTPS' => 'on',
        'SERVER_PORT' => 443,
    ],
);
$request->setLaravelSession($session);
$request->cookies->set($session->getName(), $session->getId());

$response = $kernel->handle($request);
@unlink($tmp);

echo "HTTP {$response->getStatusCode()}\n";
echo $response->getContent()."\n\n";

if ($response->getStatusCode() === 200) {
    $paths = json_decode($response->getContent(), true)['paths'] ?? [];
    foreach ($paths as $signed) {
        $plain = TemporaryUploadedFile::extractPathFromSignedPath($signed);
        $storagePath = FileUploadConfiguration::path($plain);
        $disk = FileUploadConfiguration::disk();
        $ok = Storage::disk($disk)->exists($storagePath);
        echo "disk={$disk} path={$storagePath} exists=".($ok ? 'YES '.Storage::disk($disk)->size($storagePath) : 'NO')."\n";
    }
}

$kernel->terminate($request, $response);
