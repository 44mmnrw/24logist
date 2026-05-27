<?php

header('Content-Type: text/plain; charset=utf-8');

$required = ['fileinfo', 'gd', 'exif', 'intl', 'mbstring', 'openssl'];
echo 'PHP '.PHP_VERSION.' ('.PHP_SAPI.")\n";
echo 'ini: '.(php_ini_loaded_file() ?: 'none')."\n\n";

foreach ($required as $ext) {
    echo sprintf("%-10s %s\n", $ext, extension_loaded($ext) ? 'OK' : 'MISSING');
}

if (extension_loaded('gd')) {
    echo 'GD JPEG: '.(function_exists('imagecreatefromjpeg') ? 'yes' : 'no')."\n";
    echo 'GD WebP: '.(function_exists('imagecreatefromwebp') ? 'yes' : 'no')."\n";
}

echo "\nupload_max_filesize=".ini_get('upload_max_filesize')."\n";
echo 'post_max_size='.ini_get('post_max_size')."\n";
echo 'memory_limit='.ini_get('memory_limit')."\n";
