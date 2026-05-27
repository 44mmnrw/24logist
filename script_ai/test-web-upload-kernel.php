<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;

$tmp = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($tmp, file_get_contents(__DIR__.'/../storage/app/public/landing/hero/01KSM5JX0SQ4PC56JWYFB8K43F.jpeg') ?: '');
if (filesize($tmp) < 1000) {
    file_put_contents($tmp, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdAB//2Q=='));
}
$file = new UploadedFile($tmp, 'MacBook Pro-test.jpeg', 'image/jpeg', null, true);

$uploadUrl = app(GenerateSignedUploadUrl::class)->forLocal();
$parts = parse_url($uploadUrl);

$request = Illuminate\Http\Request::create(
    ($parts['path'] ?? '').'?'.($parts['query'] ?? ''),
    'POST',
    [],
    [],
    ['files' => [$file]],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTPS' => 'on',
        'SERVER_NAME' => '24logist.ru',
    ],
);

$request->headers->set('X-CSRF-TOKEN', csrf_token());
$request->setLaravelSession($app->make('session')->driver());
$request->session()->start();

$response = $kernel->handle($request);
@unlink($tmp);

echo 'HTTP '.$response->getStatusCode()."\n";
echo $response->getContent()."\n";

if ($response->getStatusCode() === 200) {
    $paths = json_decode($response->getContent(), true)['paths'] ?? [];
    foreach ($paths as $signed) {
        $plain = Livewire\Features\SupportFileUploads\TemporaryUploadedFile::extractPathFromSignedPath($signed);
        $full = 'livewire-tmp/'.$plain;
        $exists = Illuminate\Support\Facades\Storage::disk('public')->exists($full);
        echo "{$full} exists: ".($exists ? 'yes ('.Illuminate\Support\Facades\Storage::disk('public')->size($full).' bytes)' : 'NO')."\n";
    }
}

$kernel->terminate($request, $response);
