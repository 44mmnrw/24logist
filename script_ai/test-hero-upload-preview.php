<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\FilamentUploadPreview;
use App\Support\LandingHeroCarouselForm;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

echo "=== Hero carousel upload preview (prod-like) ===\n\n";
echo 'temp_disk='.config('livewire.temporary_file_upload.disk')."\n";
echo 'app_url='.config('app.url')."\n\n";

$tmp = tempnam(sys_get_temp_dir(), 'hero');
file_put_contents($tmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$file = new UploadedFile($tmp, 'hero-test.jpeg', 'image/jpeg', null, true);

$stored = FileUploadConfiguration::storeTemporaryFile($file, FileUploadConfiguration::disk());
$plain = str_replace(FileUploadConfiguration::path('/'), '', $stored);
$signed = TemporaryUploadedFile::signPath($plain);
@unlink($tmp);

$upload = FileUpload::make('image')
    ->disk('public')
    ->directory('landing/hero')
    ->visibility('public')
    ->fetchFileInformation(false)
    ->getUploadedFileUsing(fn ($c, $f, $n) => FilamentUploadPreview::resolve($c, $f, $n));

$ref = new ReflectionClass($upload);
$method = $ref->getMethod('getUploadedFile');
$method->setAccessible(true);

$info = FilamentUploadPreview::resolve($upload, $signed, null);

echo "signed={$signed}\n";
echo 'preview_url='.($info['url'] ?? 'NULL')."\n";

if (empty($info['url'])) {
    echo "FAIL: hero preview URL empty\n";
    exit(1);
}

$persisted = LandingHeroCarouselForm::persistImage([$signed]);
echo 'persisted='.($persisted ?? 'NULL')."\n";

if ($persisted === null) {
    echo "FAIL: persistImage could not save signed upload\n";
    exit(1);
}

\Illuminate\Support\Facades\Storage::disk('public')->delete($persisted);
FileUploadConfiguration::storage()->delete(FileUploadConfiguration::path($plain));
FileUploadConfiguration::storage()->delete(FileUploadConfiguration::path($plain.'.json'));

echo "OK: hero carousel upload + preview + persist\n";
