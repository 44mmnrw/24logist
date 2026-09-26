<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Карта и расчёт маршрута — логистРу</title>
    <meta name="description" content="Постройте автомобильный или грузовой маршрут, оцените расстояние, время и стоимость перевозки.">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="{{ route('route-calculator.index') }}">
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="landing-page route-calculator-page">
    <x-landing.header />
    <main class="route-calculator-page__main">
    <div class="landing-shell">
        @include('components.landing.route-calculator-tool')
    </div>
    </main>
    <x-landing.footer />
</div>
<x-site.tracking />
</body>
</html>

