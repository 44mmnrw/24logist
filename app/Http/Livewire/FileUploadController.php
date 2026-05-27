<?php

namespace App\Http\Livewire;

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\FileUploadController as BaseFileUploadController;

class FileUploadController extends BaseFileUploadController
{
    public function handle()
    {
        abort_unless(request()->hasValidSignature(), 401);

        $disk = FileUploadConfiguration::disk();

        $filePaths = $this->validateAndStore($this->normalizeUploadedFiles(), $disk);

        return ['paths' => $filePaths];
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function normalizeUploadedFiles(): array
    {
        $files = request()->file('files');

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, fn ($file) => $file instanceof UploadedFile));
    }
}
