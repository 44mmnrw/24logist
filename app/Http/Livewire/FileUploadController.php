<?php

namespace App\Http\Livewire;

use App\Support\LivewireTemporaryStorage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController as BaseFileUploadController;

class FileUploadController extends BaseFileUploadController
{
    public function handle()
    {
        Log::info('livewire.upload-file', [
            'host' => request()->getHost(),
            'files' => count(request()->allFiles()['files'] ?? []),
        ]);

        return parent::handle();
    }

    public function validateAndStore($files, $disk)
    {
        Validator::make(['files' => $files], [
            'files.*' => FileUploadConfiguration::rules(),
        ])->validate();

        return collect($files)->map(function ($file) use ($disk) {
            $storedPath = LivewireTemporaryStorage::store($file, $disk);

            return LivewireTemporaryStorage::signStoredPath($storedPath);
        });
    }
}
