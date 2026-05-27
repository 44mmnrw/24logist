<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LandingSection;
use App\Support\LandingMedia;

$hero = LandingSection::where('slug', 'hero')->firstOrFail();
$path = 'landing/hero/01KSHTS4TFGTKN52RGR3MW9X5F.jpeg';

$page = new App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
$reflection = new ReflectionClass($page);
$method = $reflection->getMethod('mutateFormDataBeforeSave');
$method->setAccessible(true);

$cases = [
    'string path' => $path,
    'array path' => [$path],
    'uuid keyed array' => ['550e8400-e29b-41d4-a716-446655440000' => $path],
    'empty array' => [],
    'null' => null,
];

foreach ($cases as $label => $value) {
    $data = $hero->toArray();
    $data['hero_dashboard_image'] = $value;
    $saved = $method->invoke($page, $data);
    $result = $saved['extra']['dashboard_image'] ?? '(missing)';
    echo "{$label}: {$result}\n";
}

// Test actual save
$data = $hero->toArray();
$data['hero_dashboard_image'] = [$path];
$saved = $method->invoke($page, $data);
$hero->fill(collect($saved)->only($hero->getFillable())->all());
$hero->save();
$hero->refresh();
echo "\nAfter save: ".json_encode($hero->extra['dashboard_image'] ?? null)."\n";
echo "URL: ".LandingMedia::url($hero->extra['dashboard_image'] ?? null)."\n";
