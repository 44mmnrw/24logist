<?php

declare(strict_types=1);

$base = is_file(__DIR__ . '/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);
require $base . '/vendor/autoload.php';

$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'admin@24logist.ru';
$newPassword = $argv[1] ?? null;

if ($newPassword === null || $newPassword === '') {
    fwrite(STDERR, "Usage: php reset-prod-admin.php <password>\n");
    exit(1);
}

$user = User::where('email', $email)->first();

if ($user === null) {
    $user = User::create([
        'name' => 'Admin',
        'email' => $email,
        'password' => $newPassword,
        'email_verified_at' => now(),
    ]);
    echo "CREATED id={$user->id}\n";
} else {
    $user->password = $newPassword;
    $user->save();
    echo "UPDATED id={$user->id}\n";
}

$user->refresh();
$ok = Hash::check($newPassword, $user->password);
echo 'VERIFY=' . ($ok ? 'OK' : 'FAIL') . "\n";
echo "EMAIL={$user->email}\n";
