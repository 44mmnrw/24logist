<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\LandingSection;
use App\Support\LandingHeroCarousel;
use App\Support\LandingHeroCarouselForm;

$hero = LandingSection::query()->where('slug', 'hero')->firstOrFail();

$data = $hero->toArray();
$data['hero_carousel_slides'] = [
    [
        'image' => ['landing/hero/01KSFZ9B0SC6HK887ZDVFH5F1V.jpg'],
        'alt' => 'Слайд A',
    ],
    [
        'image' => ['landing/hero/01KSHTGYAWPC1MCANYHYR6GD58.jpeg'],
        'alt' => 'Слайд B',
    ],
];

$saved = LandingHeroCarouselForm::dehydrate($data);
$hero->fill(collect($saved)->only($hero->getFillable())->all());
$hero->save();
$hero->refresh();

$slides = LandingHeroCarousel::slides($hero);

if (count($slides) !== 2) {
    echo 'FAIL: expected 2 slides, got '.count($slides).PHP_EOL;
    echo json_encode($hero->extra['carousel_slides'] ?? [], JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit(1);
}

echo 'OK: saved '.count($slides).' carousel slides'.PHP_EOL;
