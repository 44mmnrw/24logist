<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$landing = app(App\Services\LandingPageService::class);
$landing->clearCache();

$items = $landing->blocks('faq', 'faq');
$html = view('components.landing.faq', ['landing' => $landing])->render();

$missing = [];

foreach ($items as $item) {
    if (blank($item->description)) {
        $missing[] = $item->title.' (empty description in DB)';
        continue;
    }

    if (! str_contains($html, e($item->description))) {
        $missing[] = $item->title.' (answer not in HTML)';
    }
}

if ($missing !== []) {
    echo "FAIL\n";
    foreach ($missing as $line) {
        echo '- '.$line.PHP_EOL;
    }
    exit(1);
}

echo "OK: {$items->count()} FAQ items, all answers rendered on frontend.\n";
