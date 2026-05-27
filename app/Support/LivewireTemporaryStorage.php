<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;

final class LivewireTemporaryStorage
{
    public static function store(UploadedFile $file, ?string $disk = null): string
    {
        $disk ??= FileUploadConfiguration::disk();
        $filename = TemporaryUploadedFile::generateHashName($file);
        $directory = FileUploadConfiguration::path('');
        $metaFilename = $filename.'.json';

        $storage = Storage::disk($disk);

        $storage->put(
            $directory.'/'.$metaFilename,
            json_encode([
                'name' => $file->getClientOriginalName(),
                'type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'hash' => $file->hashName(),
            ]),
        );

        $storedPath = $storage->putFileAs($directory, $file, $filename);

        if (! is_string($storedPath) || $storedPath === '') {
            Log::error('Livewire temporary upload failed: putFileAs returned empty path', [
                'disk' => $disk,
                'directory' => $directory,
                'filename' => $filename,
                'original' => $file->getClientOriginalName(),
            ]);

            throw new RuntimeException('Failed to store temporary upload.');
        }

        if (! $storage->exists($storedPath)) {
            Log::error('Livewire temporary upload missing after putFileAs', [
                'disk' => $disk,
                'path' => $storedPath,
            ]);

            throw new RuntimeException('Temporary upload file missing after store.');
        }

        return $storedPath;
    }

    public static function signStoredPath(string $storedPath): string
    {
        $prefix = FileUploadConfiguration::path('/');

        $stripped = str_starts_with($storedPath, $prefix)
            ? substr($storedPath, strlen($prefix))
            : $storedPath;

        return TemporaryUploadedFile::signPath($stripped);
    }
}
