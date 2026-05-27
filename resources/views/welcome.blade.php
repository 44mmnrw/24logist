<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ЛогистРу</title>
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://rsms.me">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
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
            <x-landing.quiz />
            <x-landing.faq />
            <x-landing.final-cta />
        </main>
        <x-landing.footer />
    </div>
</body>
</html>
