<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\LandingSection;
use App\Support\LandingHeroCarousel;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

$hero = LandingSection::where('slug', 'hero')->first();

if ($hero === null) {
    echo "hero section missing\n";
    exit(1);
}

echo 'carousel_slides: '.json_encode($hero->extra['carousel_slides'] ?? [], JSON_UNESCAPED_UNICODE)."\n";
echo 'dashboard_image: '.($hero->dashboard_image ?? 'null')."\n";

$slides = LandingHeroCarousel::slides($hero);
echo 'frontend slides: '.count($slides)."\n";

foreach ($slides as $i => $slide) {
    echo "  [$i] url={$slide['url']}\n";
    $path = parse_url($slide['url'], PHP_URL_PATH) ?: $slide['url'];
    $file = ltrim(str_replace('/storage/', '', $path), '/');
    echo '      exists='.(Storage::disk('public')->exists($file) ? 'yes' : 'no')." ($file)\n";
}

$link = public_path('storage');
echo 'public/storage link: '.(is_link($link) ? 'yes -> '.readlink($link) : (is_dir($link) ? 'dir' : 'missing'))."\n";

echo 'FILESYSTEM_PUBLIC_URL='.config('filesystems.disks.public.url')."\n";
