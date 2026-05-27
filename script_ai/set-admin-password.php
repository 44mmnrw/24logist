<?php

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php set-admin-password.php <new-password>\n");
    exit(1);
}

$base = is_file(__DIR__ . '/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);

require $base . '/vendor/autoload.php';

$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('email', 'admin@24logist.ru')->firstOrFail();
$user->password = $argv[1];
$user->save();

echo "Password updated for {$user->email}\n";
