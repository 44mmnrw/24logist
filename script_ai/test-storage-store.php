<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\LivewireTemporaryStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$src = __DIR__.'/../storage/app/public/landing/hero/01KSM5JX0SQ4PC56JWYFB8K43F.jpeg';
$tmp = tempnam(sys_get_temp_dir(), 'u');
file_put_contents($tmp, file_get_contents($src) ?: 'x');
$file = new UploadedFile($tmp, 'test.jpeg', 'image/jpeg', null, true);

$path = LivewireTemporaryStorage::store($file, 'public');
$exists = Storage::disk('public')->exists($path);
echo "stored={$path}\nexists=".($exists ? 'yes' : 'no')."\n";
if ($exists) {
    echo 'size='.Storage::disk('public')->size($path)."\n";
    Storage::disk('public')->delete($path);
    Storage::disk('public')->delete($path.'.json');
}

@unlink($tmp);
