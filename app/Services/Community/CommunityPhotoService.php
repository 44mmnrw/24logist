<?php

namespace App\Services\Community;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class CommunityPhotoService
{
    public const MAX_COUNT = 3;

    public const MAX_FILE_KB = 2048;

    public const MAX_SOURCE_EDGE = 3200;

    private const OUTPUT_EDGE = 1920;

    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];

    /**
     * @param  array<int, UploadedFile>  $uploads
     * @return array<int, array{path: string, width: int, height: int}>
     */
    public function storeUploads(array $uploads): array
    {
        $stored = [];

        try {
            foreach ($uploads as $upload) {
                try {
                    $photo = $this->encode($upload);
                } catch (ValidationException $e) {
                    throw $e;
                } catch (Throwable) {
                    throw $this->invalid('Не удалось обработать фото.');
                }
                $path = 'community/photos/'.Str::uuid().'.webp';

                if (! Storage::disk('local')->put($path, $photo['bytes'])) {
                    throw $this->invalid('Не удалось сохранить фото. Попробуйте ещё раз.');
                }

                $stored[] = [
                    'path' => $path,
                    'width' => $photo['width'],
                    'height' => $photo['height'],
                ];
            }
        } catch (Throwable $e) {
            $this->deleteFiles($stored);

            throw $e;
        }

        return $stored;
    }

    /** @param  iterable<array{path: string}|object>  $photos */
    public function deleteFiles(iterable $photos): void
    {
        foreach ($photos as $photo) {
            $path = is_array($photo) ? $photo['path'] : $photo->path;
            if (str_starts_with($path, 'community/photos/')) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    /** @return array{bytes: string, width: int, height: int} */
    private function encode(UploadedFile $upload): array
    {
        $path = $upload->getRealPath();
        $bytes = is_string($path) ? file_get_contents($path) : false;
        if (! $upload->isValid() || ! is_string($bytes) || $bytes === '' || strlen($bytes) > self::MAX_FILE_KB * 1024) {
            throw $this->invalid('Каждое фото должно быть не больше 2 МБ.');
        }

        $size = @getimagesizefromstring($bytes);
        if (! is_array($size)
            || ! in_array($size['mime'] ?? null, self::ALLOWED_MIME, true)
            || ($size[0] ?? 0) < 1 || ($size[1] ?? 0) < 1
            || ($size[0] ?? 0) > self::MAX_SOURCE_EDGE
            || ($size[1] ?? 0) > self::MAX_SOURCE_EDGE) {
            throw $this->invalid('Загрузите JPEG, PNG или WebP размером до 3200 × 3200 пикселей.');
        }

        $image = @imagecreatefromstring($bytes);
        if (! $image instanceof GdImage) {
            throw $this->invalid('Не удалось прочитать фото.');
        }

        $image = $this->orient($image, $upload, $size['mime']);
        $edge = max(imagesx($image), imagesy($image));
        if ($edge > self::OUTPUT_EDGE) {
            $ratio = self::OUTPUT_EDGE / $edge;
            $resized = imagescale($image, max(1, (int) round(imagesx($image) * $ratio)), max(1, (int) round(imagesy($image) * $ratio)), IMG_BICUBIC_FIXED);
            imagedestroy($image);
            if (! $resized instanceof GdImage) {
                throw $this->invalid('Не удалось обработать фото.');
            }
            $image = $resized;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $width = imagesx($image);
        $height = imagesy($image);
        ob_start();
        $saved = imagewebp($image, null, 82);
        $encoded = ob_get_clean();
        imagedestroy($image);

        if (! $saved || ! is_string($encoded) || $encoded === '') {
            throw $this->invalid('Не удалось обработать фото.');
        }

        return ['bytes' => $encoded, 'width' => $width, 'height' => $height];
    }

    private function orient(GdImage $image, UploadedFile $upload, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($upload->getRealPath());
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, in_array($orientation, [2, 5, 7], true) ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL);
        }

        $angle = match ($orientation) {
            3 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if ($rotated instanceof GdImage) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function invalid(string $message): ValidationException
    {
        return ValidationException::withMessages(['photos' => $message]);
    }
}
