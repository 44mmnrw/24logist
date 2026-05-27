<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LandingSection;
use App\Support\LandingMedia;
use Filament\Forms\Components\FileUpload;

$hero = LandingSection::where('slug', 'hero')->firstOrFail();

echo "Column dashboard_image: ".($hero->dashboard_image ?? 'null')."\n";
echo "Extra dashboard_image: ".json_encode($hero->extra['dashboard_image'] ?? null)."\n";
echo 'File exists: '.(LandingMedia::normalizePath($hero->dashboard_image) && \Illuminate\Support\Facades\Storage::disk('public')->exists($hero->dashboard_image) ? 'yes' : 'no')."\n";

$upload = FileUpload::make('dashboard_image')->disk('public')->directory('landing/hero')->visibility('public');
$ref = new ReflectionClass($upload);
$method = $ref->getMethod('getUploadedFile');
$method->setAccessible(true);
$info = $hero->dashboard_image ? $method->invoke($upload, $hero->dashboard_image, null) : null;
echo "Preview URL: ".($info['url'] ?? 'none')."\n";

$html = view('components.landing.hero', ['landing' => app(\App\Services\LandingPageService::class)])->render();
echo 'On frontend: '.(str_contains($html, LandingMedia::url($hero->dashboard_image)) ? 'yes' : 'no')."\n";
