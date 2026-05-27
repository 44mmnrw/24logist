<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

$disk = FileUploadConfiguration::disk();
$storage = Storage::disk($disk);

echo "Temp disk: {$disk}\n";
echo "Temp root: ".$storage->path('')."\n\n";

// Create temp file like Livewire would
$tmp = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$file = new Illuminate\Http\UploadedFile($tmp, 'test.png', 'image/png', null, true);
$stored = FileUploadConfiguration::storeTemporaryFile($file, $disk);
$plain = str_replace(FileUploadConfiguration::path('/'), '', $stored);
$tempFile = TemporaryUploadedFile::createFromLivewire($plain);

echo "Temp filename: ".$tempFile->getFilename()."\n";
echo "Exists: ".($tempFile->exists() ? 'yes' : 'no')."\n";
echo "Size: ".$tempFile->getSize()."\n";
echo "Mime: ".$tempFile->getMimeType()."\n";

try {
    $previewUrl = $tempFile->temporaryUrl();
    echo "Preview URL: {$previewUrl}\n";

    $request = Illuminate\Http\Request::create($previewUrl, 'GET');
    $response = $app->handle($request);
    echo "Preview HTTP status: ".$response->getStatusCode()."\n";
    echo "Preview content-type: ".$response->headers->get('content-type')."\n";
} catch (Throwable $e) {
    echo "Preview URL FAIL: ".$e->getMessage()."\n";
}

// Cleanup
$storage->delete(FileUploadConfiguration::path($plain));
$storage->delete(FileUploadConfiguration::path($plain.'.json'));
@unlink($tmp);
