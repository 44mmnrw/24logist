<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$hero = App\Models\LandingSection::where('slug', 'hero')->first();
echo json_encode($hero->extra ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
