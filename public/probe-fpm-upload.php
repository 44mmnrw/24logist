<?php

header('Content-Type: text/plain; charset=utf-8');

echo 'PHP '.PHP_VERSION.' ('.PHP_SAPI.")\n";
echo 'user='.get_current_user().' uid='.getmyuid()."\n\n";

foreach (['file_uploads', 'upload_tmp_dir', 'upload_max_filesize', 'post_max_size', 'open_basedir', 'disable_functions'] as $k) {
    echo "{$k}=".ini_get($k)."\n";
}

$tmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
echo "\nTmp writable: ".(is_writable($tmpDir) ? 'yes' : 'NO')." ({$tmpDir})\n";

$storeDir = dirname(__DIR__).'/storage/app/public/livewire-tmp';
echo 'livewire-tmp writable: '.(is_writable($storeDir) ? 'yes' : 'NO')."\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['probe'])) {
    $f = $_FILES['probe'];
    echo "\nPOST file:\n";
    echo "  error={$f['error']} size={$f['size']} tmp={$f['tmp_name']}\n";
    echo '  is_uploaded_file='.((is_uploaded_file($f['tmp_name'] ?? '')) ? 'yes' : 'no')."\n";
    $dest = $storeDir.'/probe-'.time().'.bin';
    $moved = move_uploaded_file($f['tmp_name'], $dest);
    echo '  move_uploaded_file='.($moved ? "ok -> {$dest}" : 'FAILED')."\n";
    if ($moved) {
        @unlink($dest);
    }
}
