<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forLanding($landing);
    @endphp
    <title>{{ $og['title'] }}</title>
    <x-seo.open-graph :landing="$landing" />
    <x-seo.structured-data :landing="$landing" />
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-landing.sprite />

    <div class="landing-page">
        <x-landing.header />
        <main>
            <x-landing.hero />
            {{-- <x-landing.partners /> --}}
            <x-landing.why />
            <x-landing.platform />
            <x-landing.features />
            <x-landing.pricing />
            <x-landing.mobile />
            <x-landing.driver-cabinet />
            <x-landing.quiz />
            <x-landing.faq />
            <x-landing.final-cta />
        </main>
        <x-landing.footer />
    </div>
    <x-analytics.yandex-metrika />
</body>
</html>
