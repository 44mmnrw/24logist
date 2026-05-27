<?php

use Illuminate\Contracts\Console\Kernel;
use Livewire\Features\SupportFileUploads\FileUploadController;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo get_class($app->make(FileUploadController::class))."\n";
echo 'patch='.(str_contains(file_get_contents(__DIR__.'/../vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php'), 'putFileAs(static::path()') ? 'yes' : 'no')."\n";
echo 'routes_cached='.(file_exists(__DIR__.'/../bootstrap/cache/routes-v7.php') ? 'yes' : 'no')."\n";
echo 'config_cached='.(file_exists(__DIR__.'/../bootstrap/cache/config.php') ? 'yes' : 'no')."\n";
echo 'livewire_disk='.config('livewire.temporary_file_upload.disk')."\n";

$files = glob(__DIR__.'/../storage/app/public/livewire-tmp/*');
echo 'livewire-tmp count='.count($files)."\n";
foreach (array_slice($files, -6) as $f) {
    echo basename($f).' '.filesize($f)."\n";
}
