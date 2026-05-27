<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';

use App\Support\FilamentUploadPreview;
use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

$app->make(ConsoleKernel::class)->bootstrap();

$bin = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$tf = tempnam(sys_get_temp_dir(), 't');
file_put_contents($tf, $bin);
$file = new UploadedFile($tf, 'test.png', 'image/png', null, true);
$stored = FileUploadConfiguration::storeTemporaryFile($file, FileUploadConfiguration::disk());
$stripped = str_replace(FileUploadConfiguration::path('/'), '', $stored);
$signed = TemporaryUploadedFile::signPath($stripped);
@unlink($tf);

$upload = FileUpload::make('image')->disk('public')->directory('landing/hero')->visibility('public');
$infoOld = $upload->getUploadedFile($stored, null);
$info = FilamentUploadPreview::resolve($upload, $signed, null);

echo 'Stored path: '.$stored."\n";
echo 'Signed path: '.$signed."\n";
echo 'Old getUploadedFile url: '.($infoOld['url'] ?? 'NULL')."\n";
echo 'Preview url: '.($info['url'] ?? 'NULL')."\n";
echo 'Has signature query: '.(str_contains($info['url'] ?? '', 'signature=') ? 'yes' : 'NO')."\n";
echo 'Size: '.($info['size'] ?? 0)."\n";

$plain = TemporaryUploadedFile::extractPathFromSignedPath($signed);
if ($plain !== false) {
    FileUploadConfiguration::storage()->delete(FileUploadConfiguration::path(basename($plain)));
    FileUploadConfiguration::storage()->delete(FileUploadConfiguration::path(basename($plain).'.json'));
}
