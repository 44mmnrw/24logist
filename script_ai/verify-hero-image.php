<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LandingSection;
use App\Services\LandingPageService;
use App\Support\LandingMedia;
use Illuminate\Support\Facades\Storage;

$disk = Storage::disk('public');
$disk->makeDirectory('landing/hero');

$fixture = storage_path('app/public/landing/hero/.verify-fixture.png');
if (! file_exists($fixture)) {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    file_put_contents($fixture, $png);
}

$path = 'landing/hero/.verify-fixture.png';
$hero = LandingSection::query()->where('slug', 'hero')->firstOrFail();
$extra = $hero->extra ?? [];
$extra['dashboard_image'] = ['ignored-key' => $path];
$hero->extra = $extra;
$hero->save();
$hero->refresh();

$saved = $hero->extra['dashboard_image'] ?? null;

if (! is_string($saved) || $saved !== $path) {
    echo "FAIL: expected string path, got: ".json_encode($saved).PHP_EOL;
    exit(1);
}

$url = LandingMedia::url($saved);

if ($url === null || ! str_contains($url, 'landing/hero/.verify-fixture.png')) {
    echo "FAIL: bad media url: ".($url ?? 'null').PHP_EOL;
    exit(1);
}

if (! file_exists(public_path('storage/landing/hero/.verify-fixture.png'))) {
    echo "FAIL: public storage symlink cannot reach uploaded file".PHP_EOL;
    exit(1);
}

app(LandingPageService::class)->clearCache();
$html = view('components.landing.hero', ['landing' => app(LandingPageService::class)])->render();

if (! str_contains($html, $url)) {
    echo "FAIL: hero template does not contain image url".PHP_EOL;
    exit(1);
}

echo "OK: hero image saves as string path and renders on frontend.\n";
