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

$fixturePath = 'landing/hero/.verify-upload.png';
if (! $disk->exists($fixturePath)) {
    $disk->put($fixturePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
}

$hero = LandingSection::query()->where('slug', 'hero')->firstOrFail();

// Simulate Filament form save via flat upload field.
$formData = $hero->toArray();
$formData['hero_dashboard_image'] = $fixturePath;

$page = new App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
$reflection = new ReflectionClass($page);
$method = $reflection->getMethod('mutateFormDataBeforeSave');
$method->setAccessible(true);
$savedData = $method->invoke($page, $formData);

$hero->fill(collect($savedData)->only($hero->getFillable())->all());
$hero->save();
$hero->refresh();

$path = $hero->extra['dashboard_image'] ?? null;

if (! is_string($path) || $path !== $fixturePath) {
    echo 'FAIL: dashboard_image not saved correctly: '.json_encode($path).PHP_EOL;
    exit(1);
}

app(LandingPageService::class)->clearCache();
$html = view('components.landing.hero', ['landing' => app(LandingPageService::class)])->render();

if (! str_contains($html, LandingMedia::url($path))) {
    echo 'FAIL: image not rendered in hero'.PHP_EOL;
    exit(1);
}

// Reset image for manual upload test.
$extra = $hero->extra;
unset($extra['dashboard_image']);
$hero->extra = $extra;
$hero->save();

echo "OK: hero upload field mapping works.\n";
echo 'APP_URL='.config('app.url')."\n";
echo 'storage link: '.(is_link(public_path('storage')) ? 'yes' : 'no')."\n";
