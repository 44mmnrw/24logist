<?php

namespace App\Services\Community;

use App\Models\CommunityUser;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class CommunityAvatarService
{
    private const MAX_BYTES = 3 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function syncFromProvider(CommunityUser $user, string $provider, ?string $url): void
    {
        if (! in_array($provider, ['telegram', 'max', 'vk'], true)
            || blank($url)
            || $user->avatar_source === 'custom'
            || (filled($user->avatar_path) && $user->avatar_source !== $provider)
            || ! $this->isSafeProviderUrl((string) $url)) {
            return;
        }

        try {
            $response = Http::accept('image/*')
                ->connectTimeout(3)
                ->timeout(8)
                ->withOptions(['stream' => true])
                ->get((string) $url);

            if (! $response->successful()) {
                return;
            }

            $bytes = $this->readLimited($response);

            if ($bytes === null) {
                return;
            }

            $this->storeBytes($user, $bytes, $provider);
        } catch (Throwable) {
            // Ошибка загрузки аватара не должна блокировать вход пользователя.
        }
    }

    public function storeUpload(CommunityUser $user, UploadedFile $file): bool
    {
        $path = $file->getRealPath();
        $bytes = is_string($path) ? file_get_contents($path) : false;

        return is_string($bytes) && $this->storeBytes($user, $bytes, 'custom');
    }

    public function remove(CommunityUser $user): void
    {
        $oldPath = $user->avatar_path;
        $user->forceFill(['avatar_path' => null, 'avatar_source' => null])->save();
        $this->deletePath($oldPath);
    }

    public function deletePath(?string $path): void
    {
        if (is_string($path) && str_starts_with($path, 'community/avatars/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function storeBytes(CommunityUser $user, string $bytes, string $source): bool
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            return false;
        }

        $image = @getimagesizefromstring($bytes);
        $mime = is_array($image) ? ($image['mime'] ?? null) : null;

        if (! is_string($mime)
            || ! in_array($mime, self::ALLOWED_MIME_TYPES, true)
            || ($image[0] ?? 0) < 1
            || ($image[1] ?? 0) < 1
            || ($image[0] ?? 0) > 4096
            || ($image[1] ?? 0) > 4096) {
            return false;
        }

        $normalized = $this->normalizeToWebp($bytes);

        if ($normalized === null) {
            return false;
        }

        $newPath = 'community/avatars/'.$user->id.'/'.Str::uuid().'.webp';

        if (! Storage::disk('public')->put($newPath, $normalized, ['visibility' => 'public'])) {
            return false;
        }

        $oldPath = $user->avatar_path;
        $user->forceFill(['avatar_path' => $newPath, 'avatar_source' => $source])->save();
        $this->deletePath($oldPath);

        return true;
    }

    private function normalizeToWebp(string $bytes): ?string
    {
        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $longSide = max($width, $height);
        $output = $image;

        if ($longSide > 1024) {
            $scale = 1024 / $longSide;
            $scaled = imagescale(
                $image,
                max(1, (int) round($width * $scale)),
                max(1, (int) round($height * $scale)),
                IMG_BICUBIC_FIXED,
            );

            if ($scaled === false) {
                imagedestroy($image);

                return null;
            }

            $output = $scaled;
        }

        imagepalettetotruecolor($output);
        imagealphablending($output, true);
        imagesavealpha($output, true);
        ob_start();
        $saved = imagewebp($output, null, 86);
        $normalized = ob_get_clean();

        if ($output !== $image) {
            imagedestroy($output);
        }

        imagedestroy($image);

        return $saved && is_string($normalized) && $normalized !== '' ? $normalized : null;
    }

    private function readLimited(Response $response): ?string
    {
        $stream = $response->toPsrResponse()->getBody();
        $bytes = '';

        while (! $stream->eof() && strlen($bytes) <= self::MAX_BYTES) {
            $bytes .= $stream->read(min(65536, self::MAX_BYTES + 1 - strlen($bytes)));
        }

        return strlen($bytes) <= self::MAX_BYTES ? $bytes : null;
    }

    private function isSafeProviderUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || blank($parts['host'] ?? null)
            || (isset($parts['port']) && (int) $parts['port'] !== 443)
            || isset($parts['user'])
            || isset($parts['pass'])) {
            return false;
        }

        $host = strtolower((string) $parts['host']);

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_IP) === false
            || filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
