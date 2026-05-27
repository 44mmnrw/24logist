<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;

$tmp = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($tmp, file_get_contents('https://24logist.ru/storage/landing/hero/01KSM5JX0SQ4PC56JWYFB8K43F.jpeg') ?: base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$file = new UploadedFile($tmp, 'test.jpeg', 'image/jpeg', null, true);

$url = app(GenerateSignedUploadUrl::class)->forLocal();
$path = parse_url($url, PHP_URL_PATH);
$query = parse_url($url, PHP_URL_QUERY);

echo "Upload URL: {$url}\n";

$ch = curl_init('https://24logist.ru'.$path.'?'.$query);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_POSTFIELDS => ['files' => new CURLFile($tmp, 'image/jpeg', 'test.jpeg')],
    CURLOPT_SSL_VERIFYPEER => true,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmp);

echo "HTTP {$code}\n";
echo "Body: {$body}\n";

if ($code === 200) {
    $paths = json_decode($body, true)['paths'] ?? [];
    foreach ($paths as $signed) {
        $plain = Livewire\Features\SupportFileUploads\TemporaryUploadedFile::extractPathFromSignedPath($signed);
        $full = 'livewire-tmp/'.$plain;
        $exists = Illuminate\Support\Facades\Storage::disk('public')->exists($full);
        echo "Path {$signed} -> {$full} exists: ".($exists ? 'yes' : 'NO')."\n";
    }
}
