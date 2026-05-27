<?php

header('Content-Type: text/plain; charset=utf-8');

echo "PHP ".PHP_VERSION." (".PHP_SAPI.")\n\n";

foreach (['fileinfo', 'gd', 'exif', 'intl', 'mbstring'] as $ext) {
    echo $ext.': '.(extension_loaded($ext) ? 'OK' : 'MISSING')."\n";
}

echo "\nupload_max_filesize=".ini_get('upload_max_filesize')."\n";
echo "post_max_size=".ini_get('post_max_size')."\n";
echo "max_file_uploads=".ini_get('max_file_uploads')."\n";
echo "memory_limit=".ini_get('memory_limit')."\n";

if (extension_loaded('gd')) {
    $gd = gd_info();
    echo 'GD WebP='.(($gd['WebP Support'] ?? false) ? 'yes' : 'no')."\n";
}
