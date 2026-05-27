<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$url = app(\Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl::class)->forLocal();

$tmp = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

$request = Illuminate\Http\Request::create(
    $url,
    'POST',
    [],
    [],
    ['files' => [new Illuminate\Http\UploadedFile($tmp, 'test.png', 'image/png', null, true)]],
    ['HTTP_ACCEPT' => 'application/json'],
);

$response = $app->handle($request);

echo 'Status: '.$response->getStatusCode()."\n";
echo 'Body: '.$response->getContent()."\n";

@unlink($tmp);

if ($response->getStatusCode() !== 200) {
    exit(1);
}
