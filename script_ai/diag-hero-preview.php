<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LandingSection;
use App\Support\LandingMedia;
use Filament\Forms\Components\FileUpload;

$path = LandingSection::where('slug', 'hero')->value('extra')['dashboard_image'] ?? null;
$path = LandingMedia::normalizePath($path);

echo "DB path: {$path}\n";
echo 'Exists on disk: '.(Illuminate\Support\Facades\Storage::disk('public')->exists($path) ? 'yes' : 'NO')."\n";

$upload = FileUpload::make('hero_dashboard_image')
    ->disk('public')
    ->directory('landing/hero')
    ->visibility('public');

$ref = new ReflectionClass($upload);
$method = $ref->getMethod('getUploadedFile');
$method->setAccessible(true);
$info = $path ? $method->invoke($upload, $path, null) : null;

echo "Filament file info:\n";
echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";

// Simulate form fill
$page = new App\Filament\Clusters\Landing\Resources\LandingSections\Pages\EditLandingSection;
$fillRef = new ReflectionClass($page);
$fillMethod = $fillRef->getMethod('mutateFormDataBeforeFill');
$fillMethod->setAccessible(true);
$hero = LandingSection::where('slug', 'hero')->first();
$filled = $fillMethod->invoke($page, $hero->toArray());
echo "\nForm fill hero_dashboard_image: ".json_encode($filled['hero_dashboard_image'] ?? null)."\n";
