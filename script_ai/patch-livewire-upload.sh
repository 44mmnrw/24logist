#!/usr/bin/env bash
# Fix Livewire storeAs leading slash (json saved, binary missing on some hosts).
set -euo pipefail

FILE="vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php"
ENDPOINT_FILE="vendor/livewire/livewire/src/Mechanisms/HandleRequests/EndpointResolver.php"

if [[ ! -f "$FILE" ]]; then
  echo "[patch] skip: $FILE not found"
  exit 0
fi
if [[ ! -f "$ENDPOINT_FILE" ]]; then
  echo "[patch] skip: $ENDPOINT_FILE not found"
  exit 0
fi

php <<'PHP'
<?php
$file = 'vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php';
$content = file_get_contents($file);
$changed = false;

$replacements = [
    [
        "Storage::disk(\$disk)->put('/'.static::path(\$metaFilename), json_encode([",
        "Storage::disk(\$disk)->put(static::path(\$metaFilename), json_encode([",
        'meta put leading slash',
    ],
    [
        <<<'OLD'
        return $file->storeAs('/'.static::path(), $filename, [
            'disk' => $disk
        ]);
OLD,
        <<<'NEW'
        $stored = \Illuminate\Support\Facades\Storage::disk($disk)->putFileAs(static::path(), $file, $filename);

        if (! is_string($stored) || $stored === '') {
            throw new \RuntimeException('Livewire failed to store temporary upload.');
        }

        return $stored;
NEW,
        'storeAs leading slash',
    ],
];

foreach ($replacements as [$old, $new, $label]) {
    if (! str_contains($content, $old)) {
        continue;
    }

    $content = str_replace($old, $new, $content);
    $changed = true;
    echo "[patch] fixed: {$label}\n";
}

if (! $changed) {
    echo "[patch] already applied\n";
    exit(0);
}

file_put_contents($file, $content);
echo "[patch] Livewire FileUploadConfiguration updated\n";
PHP

php <<'PHP'
<?php
$file = 'vendor/livewire/livewire/src/Mechanisms/HandleRequests/EndpointResolver.php';
$content = file_get_contents($file);
$updated = str_replace(
    "return static::prefix() . '/upload-file';",
    "return static::prefix() . '/upload';",
    $content,
    $count,
);

if ($count > 0) {
    file_put_contents($file, $updated);
    echo "[patch] fixed: upload endpoint path\n";
} else {
    echo "[patch] upload endpoint already patched\n";
}
PHP
