#!/usr/bin/env bash
# Fix Livewire storeAs leading slash (json saved, binary missing on some hosts).
set -euo pipefail

FILE="vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php"

if [[ ! -f "$FILE" ]]; then
  echo "[patch] skip: $FILE not found"
  exit 0
fi

if grep -q 'putFileAs(static::path()' "$FILE"; then
  echo "[patch] already applied"
  exit 0
fi

php <<'PHP'
<?php
$file = 'vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php';
$content = file_get_contents($file);
$old = <<<'OLD'
        return $file->storeAs('/'.static::path(), $filename, [
            'disk' => $disk
        ]);
OLD;
$new = <<<'NEW'
        $stored = \Illuminate\Support\Facades\Storage::disk($disk)->putFileAs(static::path(), $file, $filename);

        if (! is_string($stored) || $stored === '') {
            throw new \RuntimeException('Livewire failed to store temporary upload.');
        }

        return $stored;
NEW;
if (! str_contains($content, $old)) {
    fwrite(STDERR, "[patch] pattern not found in FileUploadConfiguration.php\n");
    exit(1);
}
file_put_contents($file, str_replace($old, $new, $content));
echo "[patch] Livewire FileUploadConfiguration updated\n";
PHP
