<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$hero = App\Models\LandingSection::where('slug', 'hero')->firstOrFail();
$extra = $hero->extra;
$extra['dashboard_image'] = 'landing/hero/01KSHTS4TFGTKN52RGR3MW9X5F.jpeg';
$hero->extra = $extra;
$hero->save();
app(App\Services\LandingPageService::class)->clearCache();
echo "Restored dashboard_image\n";
