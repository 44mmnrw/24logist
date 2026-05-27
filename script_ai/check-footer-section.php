<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$section = App\Models\LandingSection::query()->where('slug', 'footer')->first();

if ($section === null) {
    echo "footer section: MISSING\n";
    exit(1);
}

$columns = App\Models\LandingBlock::query()
    ->where('section_slug', 'footer')
    ->where('block_type', 'footer_column')
    ->count();

echo "footer section: OK\n";
echo "description: ".($section->description ?? '—')."\n";
echo "copyright: ".($section->extra['copyright'] ?? '—')."\n";
echo "columns: {$columns}\n";
