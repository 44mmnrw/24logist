<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

$bin = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$tf = tempnam(sys_get_temp_dir(), 't');
file_put_contents($tf, $bin);
$file = new UploadedFile($tf, 'test.png', 'image/png', null, true);
$signed = FileUploadConfiguration::storeTemporaryFile($file, FileUploadConfiguration::disk());
$plain = TemporaryUploadedFile::extractPathFromSignedPath($signed);
$tmp = TemporaryUploadedFile::createFromLivewire($plain);
echo "TemporaryUploadedFile::temporaryUrl(): ".$tmp->temporaryUrl()."\n";
@unlink($tf);

$upload = FileUpload::make('image')->disk('public')->directory('landing/hero');
$ref = new ReflectionClass($upload);
$method = $ref->getMethod('getUploadedFile');
$method->setAccessible(true);
$info = $method->invoke($upload, $tmp, null);
echo "Filament getUploadedFile url (temp): ".($info['url'] ?? 'null')."\n";

// Simulate relativeUploadUrl
$url = $info['url'] ?? '';
$path = parse_url($url, PHP_URL_PATH);
$stripped = is_string($path) && $path !== '' ? $path : $url;
echo "After strip: {$stripped}\n";
echo "Query lost: ".(parse_url($url, PHP_URL_QUERY) && ! str_contains($stripped, '?') ? 'YES - BUG' : 'no')."\n";
