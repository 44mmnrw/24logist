<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forPage($page);
    @endphp
    <title>{{ $og['title'] }}</title>
    <x-seo.open-graph :page="$page" />
    <x-seo.structured-data :page="$page" />
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-landing.sprite />

    <div class="landing-page">
        <x-landing.header />
        <main class="cms-page">
            <div class="landing-shell">
                <article class="cms-page__article">
                    <a class="cms-page__back" href="{{ url('/') }}">← На главную</a>
                    <h1>{{ $page->title }}</h1>
                    <div class="cms-page__body">
                        {!! $page->renderBody() !!}
                    </div>
                </article>
            </div>
        </main>
        <x-landing.footer />
    </div>
    <x-analytics.yandex-metrika />
</body>
</html>
