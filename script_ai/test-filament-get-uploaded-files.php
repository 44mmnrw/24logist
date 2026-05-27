<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use App\Models\LandingSection;
use App\Support\FilamentUploadPreview;
use App\Support\LandingHeroCarouselForm;
use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\Console\Kernel;

$hero = LandingSection::where('slug', 'hero')->first();
$data = LandingHeroCarouselForm::hydrate($hero->toArray());
$slide = $data['hero_carousel_slides'][0] ?? [];
$path = $slide['image'][0] ?? null;

echo "path={$path}\n";

$upload = FileUpload::make('image')
    ->disk('public')
    ->directory('landing/hero')
    ->visibility('public')
    ->getUploadedFileUsing(fn ($c, $f, $n) => FilamentUploadPreview::resolve($c, $f, $n));

$ref = new ReflectionClass($upload);
$method = $ref->getMethod('getUploadedFile');
$method->setAccessible(true);

$info = $method->invoke($upload, $path, null);
echo 'preview: '.json_encode($info, JSON_UNESCAPED_UNICODE)."\n";

if (empty($info['url'])) {
    echo "FAIL: no preview URL\n";
    exit(1);
}

echo "OK\n";
