<?php

declare(strict_types=1);

$file = __DIR__.'/../vendor/livewire/livewire/src/Mechanisms/HandleRequests/EndpointResolver.php';

if (! is_file($file)) {
    fwrite(STDERR, "file not found: {$file}\n");
    exit(1);
}

$content = file_get_contents($file);

if ($content === false) {
    fwrite(STDERR, "failed to read file\n");
    exit(1);
}

$updated = str_replace(
    "return static::prefix() . '/upload-file';",
    "return static::prefix() . '/upload';",
    $content,
    $count,
);

if ($count > 0) {
    file_put_contents($file, $updated);
}

echo "patched={$count}\n";
