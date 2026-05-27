<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\FilamentUploadPreview;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

echo "PHP ".PHP_VERSION.' ('.PHP_SAPI.")\n";
echo 'APP_URL='.config('app.url')."\n\n";

$exts = ['fileinfo', 'gd', 'exif', 'intl', 'mbstring', 'openssl'];
foreach ($exts as $ext) {
    echo sprintf("%-10s %s\n", $ext, extension_loaded($ext) ? 'OK' : 'MISSING');
}

if (extension_loaded('gd')) {
    echo 'GD JPEG: '.(function_exists('imagecreatefromjpeg') ? 'yes' : 'no')."\n";
    echo 'GD WebP: '.(function_exists('imagecreatefromwebp') ? 'yes' : 'no')."\n";
}

echo "\n=== Temp upload + preview URL ===\n";
$bin = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$tf = tempnam(sys_get_temp_dir(), 't');
file_put_contents($tf, $bin);
$file = new UploadedFile($tf, 'test.jpeg', 'image/jpeg', null, true);
$stored = FileUploadConfiguration::storeTemporaryFile($file, FileUploadConfiguration::disk());
$stripped = str_replace(FileUploadConfiguration::path('/'), '', $stored);
$signed = TemporaryUploadedFile::signPath($stripped);
@unlink($tf);

$upload = FileUpload::make('image')->disk('public')->directory('landing/hero')->visibility('public');
$info = FilamentUploadPreview::resolve($upload, $signed, null);

echo "Signed: {$signed}\n";
echo 'File on disk: '.(Storage::disk('public')->exists($stored) ? 'yes' : 'NO')."\n";
echo 'Preview URL: '.($info['url'] ?? 'NULL')."\n";

if (! empty($info['url'])) {
    $previewPath = parse_url($info['url'], PHP_URL_PATH).'?'.parse_url($info['url'], PHP_URL_QUERY);
    $request = Illuminate\Http\Request::create($previewPath, 'GET');
    $valid = Illuminate\Support\Facades\URL::hasValidSignature($request);
    echo 'Signature valid: '.($valid ? 'yes' : 'no')."\n";
}

Storage::disk('public')->delete($stored);
Storage::disk('public')->delete($stored.'.json');

echo "\n=== livewire-tmp contents ===\n";
foreach (Storage::disk('public')->files('livewire-tmp') as $f) {
    echo $f.' ('.Storage::disk('public')->size($f)." bytes)\n";
}
