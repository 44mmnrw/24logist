<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$items = App\Models\LandingBlock::query()
    ->where('section_slug', 'faq')
    ->where('block_type', 'faq')
    ->orderBy('sort_order')
    ->get(['id', 'title', 'description']);

foreach ($items as $item) {
    echo $item->id.' | '.$item->title.' | '.($item->description ?: '(empty)').PHP_EOL;
}

echo 'total: '.$items->count().PHP_EOL;
