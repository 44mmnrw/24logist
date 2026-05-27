<?php

namespace App\Support;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

final class FilamentUploadPreview
{
    /**
     * @param  string|array<string, string>|null  $storedFileNames
     * @return array{name: string, size: int, type: ?string, url: ?string, openableUrl?: string, downloadableUrl?: string}|null
     */
    public static function resolve(FileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        if (TemporaryUploadedFile::extractPathFromSignedPath($file) !== false) {
            return self::temporaryFileInfo($file);
        }

        $info = $component->getUploadedFile($file, $storedFileNames);

        if ($info === null) {
            return null;
        }

        foreach (['url', 'openableUrl', 'downloadableUrl'] as $key) {
            if (! isset($info[$key]) || ! is_string($info[$key])) {
                continue;
            }

            $info[$key] = self::publicStoragePathOnly($info[$key]);
        }

        return $info;
    }

    /**
     * @return array{name: string, size: int, type: ?string, url: string, openableUrl: string, downloadableUrl: string}|null
     */
    private static function temporaryFileInfo(string $signedPath): ?array
    {
        $plainPath = TemporaryUploadedFile::extractPathFromSignedPath($signedPath);

        if ($plainPath === false) {
            return null;
        }

        $disk = FileUploadConfiguration::disk();
        $storagePath = FileUploadConfiguration::path($plainPath);

        $storage = Storage::disk($disk);

        if (! $storage->exists($storagePath)) {
            Log::warning('Livewire temp upload missing on disk', [
                'signed' => $signedPath,
                'path' => $storagePath,
                'disk' => $disk,
            ]);

            return null;
        }

        try {
            $temp = TemporaryUploadedFile::createFromLivewire($plainPath);
            $meta = $temp->metaFileData();

            // Public disk: same as local — direct /storage URL (no signed preview-file route).
            if ($disk === 'public') {
                $url = self::publicStoragePathOnly($storage->url($storagePath));

                return [
                    'name' => (string) ($meta['name'] ?? $temp->getClientOriginalName()),
                    'size' => (int) ($meta['size'] ?? $temp->getSize()),
                    'type' => (string) ($meta['type'] ?? $temp->getMimeType()),
                    'url' => $url,
                    'openableUrl' => $url,
                    'downloadableUrl' => $url,
                ];
            }

            $url = $temp->temporaryUrl();

            return [
                'name' => (string) ($meta['name'] ?? $temp->getClientOriginalName()),
                'size' => (int) ($meta['size'] ?? $temp->getSize()),
                'type' => (string) ($meta['type'] ?? $temp->getMimeType()),
                'url' => $url,
                'openableUrl' => $url,
                'downloadableUrl' => $url,
            ];
        } catch (Throwable $exception) {
            Log::warning('Livewire temp preview URL failed', [
                'signed' => $signedPath,
                'path' => $storagePath,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private static function publicStoragePathOnly(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || ! str_starts_with($path, '/storage/')) {
            return $url;
        }

        return $path;
    }
}
