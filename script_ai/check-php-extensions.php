<?php

/**
 * CLI PHP extension check for Filament/Livewire uploads.
 * Run: php script_ai/check-php-extensions.php
 */
echo "PHP ".PHP_VERSION." (".PHP_SAPI.")\n\n";

$required = [
    'fileinfo' => 'MIME types for uploads',
    'gd' => 'image preview/processing',
    'exif' => 'EXIF orientation',
    'mbstring' => 'Laravel strings',
    'openssl' => 'signed upload URLs',
    'curl' => 'HTTP client',
    'intl' => 'Filament i18n',
    'pdo_mysql' => 'DB sessions',
];

$missing = [];
foreach ($required as $ext => $desc) {
    $ok = extension_loaded($ext);
    echo ($ok ? '[OK] ' : '[MISSING] ')."{$ext} — {$desc}\n";
    if (! $ok) {
        $missing[] = $ext;
    }
}

echo "\nLimits: upload_max=".ini_get('upload_max_filesize')
    .', post_max='.ini_get('post_max_size')
    .', memory='.ini_get('memory_limit')."\n";

if (extension_loaded('gd')) {
    $gd = gd_info();
    echo 'GD: JPEG='.($gd['JPEG Support'] ?? '?')
        .', PNG='.($gd['PNG Support'] ?? '?')
        .', WebP='.($gd['WebP Support'] ?? '?')."\n";
}

if ($missing) {
    echo "\nEnable in Laragon: Menu → PHP → Extensions → ".implode(', ', $missing)."\n";
    exit(1);
}

echo "\nAll required extensions are present.\n";
