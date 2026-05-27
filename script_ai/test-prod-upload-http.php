<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;

$tmp = tempnam(sys_get_temp_dir(), 'img');
$bin = @file_get_contents(__DIR__.'/../storage/app/public/landing/hero/01KSM5JX0SQ4PC56JWYFB8K43F.jpeg');
if ($bin === false || strlen($bin) < 1000) {
    $bin = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdAB//2Q==');
}
file_put_contents($tmp, $bin);
$file = new UploadedFile($tmp, 'test.jpeg', 'image/jpeg', null, true);

$url = app(GenerateSignedUploadUrl::class)->forLocal();
$parts = parse_url($url);

$ch = curl_init('https://24logist.ru'.($parts['path'] ?? '').'?'.($parts['query'] ?? ''));
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Requested-With: XMLHttpRequest'],
    CURLOPT_POSTFIELDS => ['files' => new CURLFile($tmp, 'image/jpeg', 'test.jpeg')],
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmp);

echo "HTTP {$code}\n{$body}\n";

if ($code === 200) {
    $paths = json_decode($body, true)['paths'] ?? [];
    foreach ($paths as $signed) {
        $plain = Livewire\Features\SupportFileUploads\TemporaryUploadedFile::extractPathFromSignedPath($signed);
        $full = 'livewire-tmp/'.$plain;
        $exists = Illuminate\Support\Facades\Storage::disk('public')->exists($full);
        echo "{$full}: ".($exists ? 'EXISTS ('.Illuminate\Support\Facades\Storage::disk('public')->size($full).' bytes)' : 'MISSING')."\n";
    }
}
