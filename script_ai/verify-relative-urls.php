<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Simulate web request from localhost
$request = Illuminate\Http\Request::create('http://localhost/admin', 'GET');
$app->instance('request', $request);

Illuminate\Support\Facades\URL::forceRootUrl($request->getSchemeAndHttpHost());

use App\Models\LandingSection;
use App\Support\LandingMedia;
use Filament\Forms\Components\FileUpload;

$hero = LandingSection::where('slug', 'hero')->firstOrFail();

echo "dashboard_image: {$hero->dashboard_image}\n";
echo 'Storage URL: '.Illuminate\Support\Facades\Storage::disk('public')->url($hero->dashboard_image)."\n";
echo 'LandingMedia URL: '.LandingMedia::url($hero->dashboard_image)."\n";

$upload = FileUpload::make('dashboard_image')
    ->disk('public')
    ->directory('landing/hero')
    ->visibility('public')
    ->getUploadedFileUsing(function (FileUpload $component, string $file, string|array|null $storedFileNames): ?array {
        $info = $component->getUploadedFile($file, $storedFileNames);
        if ($info !== null && isset($info['url'])) {
            $path = parse_url($info['url'], PHP_URL_PATH);
            if (is_string($path) && $path !== '') {
                $info['url'] = $path;
            }
        }
        return $info;
    });

$ref = new ReflectionClass($upload);
$method = $ref->getMethod('getUploadedFiles');
$method->setAccessible(true);

// Bind state for getUploadedFiles
$stateRef = new ReflectionClass($upload);
$statePath = 'dashboard_image';
$upload->state($hero->dashboard_image);

$files = $method->invoke($upload);
echo "Filament preview URL: ".json_encode($files, JSON_UNESCAPED_UNICODE)."\n";

app(\App\Services\LandingPageService::class)->clearCache();
$html = view('components.landing.hero', ['landing' => app(\App\Services\LandingPageService::class)])->render();
echo 'Frontend has img src: '.(str_contains($html, '/storage/landing/hero/') ? 'yes' : 'no')."\n";
