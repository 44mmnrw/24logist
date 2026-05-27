<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

$tmp = tempnam(sys_get_temp_dir(), 't');
file_put_contents($tmp, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdAB//2Q=='));
$file = new UploadedFile($tmp, 'test.jpeg', 'image/jpeg', null, true);
$stored = FileUploadConfiguration::storeTemporaryFile($file, FileUploadConfiguration::disk());
$stripped = str_replace(FileUploadConfiguration::path('/'), '', $stored);
$signed = TemporaryUploadedFile::signPath($stripped);
$temp = TemporaryUploadedFile::createFromLivewire($stripped);
$url = $temp->temporaryUrl();
@unlink($tmp);

echo "Preview URL:\n{$url}\n\n";

$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true, CURLOPT_FOLLOWLOCATION => true]);
curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP status: {$code}\n";
echo 'File exists: '.(Illuminate\Support\Facades\Storage::disk('public')->exists($stored) ? 'yes' : 'no')."\n";

Illuminate\Support\Facades\Storage::disk('public')->delete($stored);
Illuminate\Support\Facades\Storage::disk('public')->delete($stored.'.json');
