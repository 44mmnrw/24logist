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
            @php
                $sectionViews = [
                    'hero' => 'components.landing.hero',
                    'why' => 'components.landing.why',
                    'platform' => 'components.landing.platform',
                    'features' => 'components.landing.features',
                    'pricing' => 'components.landing.pricing',
                    'epd_platform' => 'components.landing.epd-platform',
                    'mobile' => 'components.landing.mobile',
                    'driver_cabinet' => 'components.landing.driver-cabinet',
                    'quiz' => 'components.landing.quiz',
                    'faq' => 'components.landing.faq',
                    'final_cta' => 'components.landing.final-cta',
                ];
                $alternatingSections = [
                    'why', 'platform', 'features', 'pricing', 'epd_platform',
                    'mobile', 'driver_cabinet', 'quiz', 'faq',
                ];
                $alternatingIndex = 0;
            @endphp
            @foreach ($landing->sections() as $landingSection)
                @if (isset($sectionViews[$landingSection->slug]))
                    @if (in_array($landingSection->slug, $alternatingSections, true))
                        <div class="landing-section-slot landing-section-slot--{{ $alternatingIndex % 2 === 0 ? 'white' : 'light' }}">
                            @include($sectionViews[$landingSection->slug])
                        </div>
                        @php($alternatingIndex++)
                    @else
                        @include($sectionViews[$landingSection->slug])
                    @endif
                @endif
            @endforeach
        </main>
        <x-landing.footer />
    </div>
    <x-analytics.yandex-metrika />
</body>
</html>
