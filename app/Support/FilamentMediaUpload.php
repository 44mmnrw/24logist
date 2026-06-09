<?php

namespace App\Support;

use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

final class FilamentMediaUpload
{
    public static function persist(mixed $state, string $directory): ?string
    {
        if ($state instanceof TemporaryUploadedFile) {
            return $state->store($directory, 'public');
        }

        if (is_array($state)) {
            foreach ($state as $item) {
                $stored = self::persist($item, $directory);

                if ($stored !== null) {
                    return $stored;
                }
            }

            return null;
        }

        return LandingMedia::normalizePath($state);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function wrapPathForFill(array $data, string $field): array
    {
        if (filled($data[$field] ?? null)) {
            $data[$field] = [(string) $data[$field]];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function wrapExtraPathForFill(array $extra, string $key): array
    {
        if (filled($extra[$key] ?? null)) {
            $extra[$key] = [(string) $extra[$key]];
        }

        return $extra;
    }
}
