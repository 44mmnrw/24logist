<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\LandingPageService::class);
$service->clearCache();
$pricing = $service->section('pricing');
echo $pricing?->extra['footnote'] ?? 'FAIL';
echo PHP_EOL;
