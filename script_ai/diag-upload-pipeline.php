<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;

echo "=== Livewire upload end-to-end test ===\n\n";

// 1. Signed URL
$url = app(GenerateSignedUploadUrl::class)->forLocal();
echo "Signed URL host: ".parse_url($url, PHP_URL_HOST)."\n";
echo "APP_URL: ".config('app.url')."\n\n";

// 2. Create test image
$tmp = tempnam(sys_get_temp_dir(), 'img');
file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$file = new UploadedFile($tmp, 'test.png', 'image/png', null, true);

// 3. Simulate signed upload WITH CSRF bypass via withoutMiddleware in test kernel
$request = Request::create(
    parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY),
    'POST',
    [],
    [],
    ['files' => [$file]],
    [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X-CSRF-TOKEN' => 'test',
    ],
);

// Validate signature only (skip CSRF for this internal test)
URL::forceRootUrl(config('app.url'));
$validSig = URL::hasValidSignature($request);
echo 'Valid signature: '.($validSig ? 'yes' : 'no')."\n";

// Direct controller call bypassing middleware
$controller = app(\Livewire\Features\SupportFileUploads\FileUploadController::class);
try {
    request()->merge(['files' => [$file]]);
    // Use reflection to call validateAndStore directly
    $ref = new ReflectionClass($controller);
    $method = $ref->getMethod('validateAndStore');
    $method->setAccessible(true);
    $paths = $method->invoke($controller, [$file], FileUploadConfiguration::disk());
    echo 'Upload stored paths: '.json_encode($paths)."\n";

    $disk = FileUploadConfiguration::storage();
    foreach ($paths as $signedPath) {
        $plain = \Livewire\Features\SupportFileUploads\TemporaryUploadedFile::extractPathFromSignedPath($signedPath);
        $full = $disk->path(FileUploadConfiguration::path($plain));
        echo 'File on disk: '.(file_exists($full) ? 'yes ('.filesize($full).' bytes)' : 'NO')."\n";
        // cleanup
        $disk->delete(FileUploadConfiguration::path($plain));
        $disk->delete(FileUploadConfiguration::path($plain.'.json'));
    }
    echo "\nRESULT: Server-side upload pipeline OK\n";
} catch (Throwable $e) {
    echo 'FAIL: '.$e->getMessage()."\n";
    exit(1);
} finally {
    @unlink($tmp);
}

// 4. Test getUploadedFile (Filament path)
$upload = \Filament\Forms\Components\FileUpload::make('test')
    ->disk('public')
    ->directory('landing/hero');
$storage = \Illuminate\Support\Facades\Storage::disk('public');
$testPath = 'landing/hero/.diag-test.png';
$storage->put($testPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));

$ref = new ReflectionClass($upload);
$method = $ref->getMethod('getUploadedFile');
$method->setAccessible(true);
$info = $method->invoke($upload, $testPath, null);
echo "\nFilament getUploadedFile:\n";
echo '  size: '.($info['size'] ?? 'null')."\n";
echo '  type: '.($info['type'] ?? 'null')."\n";
echo '  url: '.($info['url'] ?? 'null')."\n";
$storage->delete($testPath);
