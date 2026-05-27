<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

$disk = FileUploadConfiguration::disk();
$storage = Storage::disk($disk);

echo "Disk: {$disk}\n";
echo "Root: ".$storage->path('')."\n";

// Create tiny test image
$tmp = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

$file = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

try {
    $path = FileUploadConfiguration::storeTemporaryFile($file, $disk);
    echo "Stored path: {$path}\n";

    $basename = basename(str_replace(FileUploadConfiguration::path('/'), '', $path));
    $fullPath = $storage->path(FileUploadConfiguration::path($basename));
    $jsonPath = $fullPath.'.json';

    echo "File exists: ".(file_exists($fullPath) ? 'yes' : 'no')."\n";
    echo "JSON exists: ".(file_exists($jsonPath) ? 'yes' : 'no')."\n";
    echo "File size: ".(file_exists($fullPath) ? filesize($fullPath) : 0)."\n";

    // Cleanup
    if ($storage->exists(FileUploadConfiguration::path($basename))) {
        $storage->delete(FileUploadConfiguration::path($basename));
    }
    if ($storage->exists(FileUploadConfiguration::path($basename.'.json'))) {
        $storage->delete(FileUploadConfiguration::path($basename.'.json'));
    }
    echo "OK: storeTemporaryFile works\n";
} catch (Throwable $e) {
    echo 'FAIL: '.$e->getMessage()."\n";
    exit(1);
} finally {
    @unlink($tmp);
}

// Test public disk for final storage
$public = Storage::disk('public');
$public->makeDirectory('landing/hero');
$publicPath = 'landing/hero/test-'.time().'.png';
$public->put($publicPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
echo "Public disk write: ".($public->exists($publicPath) ? 'yes' : 'no')."\n";
$public->delete($publicPath);

// Signed URL test
$url = app(\Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl::class)->forLocal();
echo "Signed upload URL: {$url}\n";
echo 'APP_URL='.config('app.url')."\n";
