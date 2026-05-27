<?php

declare(strict_types=1);

$base = is_file(__DIR__ . '/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);
require $base . '/vendor/autoload.php';

$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;

$email = 'admin@24logist.ru';
$password = $argv[1] ?? '';

$ok = Auth::attempt(['email' => $email, 'password' => $password]);
echo $ok ? "AUTH_OK\n" : "AUTH_FAIL\n";
