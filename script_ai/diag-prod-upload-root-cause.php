<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;

echo "=== PROD UPLOAD ROOT CAUSE DIAG ===\n\n";

echo "--- Laravel caches ---\n";
echo 'bootstrap/cache/config.php: '.(is_file(base_path('bootstrap/cache/config.php')) ? 'YES (config cached)' : 'no')."\n";
echo 'bootstrap/cache/routes-v7.php: '.(glob(base_path('bootstrap/cache/routes-*.php')) ? 'YES (routes cached)' : 'no')."\n";

echo "\n--- Env / config ---\n";
echo 'APP_URL='.config('app.url')."\n";
echo 'APP_ENV='.config('app.env')."\n";
echo 'FILESYSTEM_PUBLIC_URL='.config('filesystems.disks.public.url')."\n";
echo 'livewire.temp_disk='.config('livewire.temporary_file_upload.disk')."\n";
echo 'livewire.temp_dir='.config('livewire.temporary_file_upload.directory')."\n";

echo "\n--- Livewire temp storage ---\n";
$disk = FileUploadConfiguration::disk();
$storage = FileUploadConfiguration::storage();
$tmpPath = FileUploadConfiguration::path('');
echo "disk={$disk}\n";
echo "path_prefix={$tmpPath}\n";
echo 'root='.($storage->path('') ?: '(empty)')."\n";

$glob = glob($storage->path($tmpPath).'/*') ?: [];
$jsonOnly = 0;
$binaries = 0;
foreach ($glob as $f) {
    if (is_file($f) && str_ends_with($f, '.json')) {
        $jsonOnly++;
    } elseif (is_file($f)) {
        $binaries++;
    }
}
echo 'livewire-tmp files: '.count($glob)." (json={$jsonOnly}, binary={$binaries})\n";

echo "\n--- Signed upload URL (CLI, no request host) ---\n";
try {
    $url = app(GenerateSignedUploadUrl::class)->forLocal();
    echo "url={$url}\n";
    $host = parse_url($url, PHP_URL_HOST);
    $path = parse_url($url, PHP_URL_PATH);
    echo "host={$host} path={$path}\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}

echo "\n--- Signed URL with simulated browser request ---\n";
$request = Request::create('https://24logist.ru/admin', 'GET', server: [
    'HTTP_HOST' => '24logist.ru',
    'HTTPS' => 'on',
    'SERVER_PORT' => '443',
]);
$app->instance('request', $request);
URL::forceRootUrl('https://24logist.ru');
try {
    $url2 = app(GenerateSignedUploadUrl::class)->forLocal();
    echo "url={$url2}\n";
} catch (Throwable $e) {
    echo 'ERROR: '.$e->getMessage()."\n";
}

echo "\n--- Livewire upload routes ---\n";
$found = false;
foreach (Route::getRoutes() as $route) {
    $uri = $route->uri();
    if (str_contains($uri, 'livewire') && (str_contains($uri, 'upload') || str_contains($uri, 'preview'))) {
        echo $route->methods()[0].' '.$uri.' -> '.($route->getActionName())."\n";
        $found = true;
    }
}
if (! $found) {
    echo "NO livewire upload routes found!\n";
}

echo "\n--- Custom FileUploadController binding ---\n";
echo get_class($app->make(FileUploadController::class))."\n";

echo "\n--- Livewire vendor patch ---\n";
$vendorFile = base_path('vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php');
if (is_file($vendorFile)) {
    $c = file_get_contents($vendorFile);
    echo 'putFileAs patch: '.(str_contains($c, 'putFileAs(static::path()') ? 'yes' : 'no')."\n";
    echo 'storeAs slash bug: '.(str_contains($c, "storeAs('/".'".static::path()') || str_contains($c, "storeAs('/") ? 'maybe' : 'no')."\n";
} else {
    echo "vendor file missing\n";
}

echo "\n--- Public disk livewire-tmp ---\n";
$pub = Storage::disk('public');
$pubTmp = 'livewire-tmp';
if ($pub->exists($pubTmp)) {
    $files = $pub->files($pubTmp);
    echo 'public/livewire-tmp count='.count($files)."\n";
    foreach (array_slice($files, 0, 5) as $f) {
        echo "  {$f} size=".$pub->size($f)."\n";
    }
} else {
    echo "public/livewire-tmp missing\n";
}

echo "\n--- Recent livewire upload logs ---\n";
$logFile = storage_path('logs/laravel.log');
if (is_file($logFile)) {
    $lines = file($logFile) ?: [];
    $hits = array_filter($lines, fn ($l) => str_contains($l, 'livewire.upload') || str_contains($l, 'Livewire temp'));
    echo implode('', array_slice($hits, -10));
    if ($hits === []) {
        echo "(no livewire.upload entries — upload-file likely never hits server)\n";
    }
}

echo "\n=== END ===\n";
