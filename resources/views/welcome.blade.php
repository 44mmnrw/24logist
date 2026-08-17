<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <x-analytics.google-analytics />
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
        <main class="landing-route" data-landing-route>
            <div class="landing-route__overlay" data-landing-route-overlay aria-hidden="true">
                <svg class="landing-route__svg" data-landing-route-svg preserveAspectRatio="none">
                    <path class="landing-route__path landing-route__path--road-edge" data-landing-route-path />
                    <path class="landing-route__path landing-route__path--road-surface" data-landing-route-path />
                    <path class="landing-route__path landing-route__path--road-marking" data-landing-route-path />
                    <path class="landing-route__path landing-route__path--progress" data-landing-route-path data-landing-route-progress />
                </svg>
            </div>
            <div class="landing-route__markers" data-landing-route-markers aria-hidden="true"></div>
            @php
                $sectionViews = [
                    'hero' => 'components.landing.hero',
                    'why' => 'components.landing.why',
                    'platform' => 'components.landing.platform',
                    'features' => 'components.landing.features',
                    'pricing' => 'components.landing.pricing',
                    'additional_options' => 'components.landing.additional-options',
                    'epd_platform' => 'components.landing.epd-platform',
                    'mobile' => 'components.landing.mobile',
                    'driver_cabinet' => 'components.landing.driver-cabinet',
                    'quiz' => 'components.landing.quiz',
                    'faq' => 'components.landing.faq',
                    'final_cta' => 'components.landing.final-cta',
                ];
                $specialBackgroundSections = ['hero', 'final_cta'];
                $alternatingIndex = 0;
            @endphp
            @foreach ($landing->sections() as $landingSection)
                @if (isset($sectionViews[$landingSection->slug]))
                    @php
                        $isRouteStop = $landingSection->route_enabled && filled($landingSection->route_label);
                    @endphp
                    <div
                        class="landing-route__section"
                        @if ($isRouteStop)
                            data-route-stop
                            data-route-label="{{ $landingSection->route_label }}"
                        @endif
                    >
                        @if (! in_array($landingSection->slug, $specialBackgroundSections, true))
                            <div class="landing-section-slot landing-section-slot--{{ $alternatingIndex % 2 === 0 ? 'white' : 'light' }}">
                                @include($sectionViews[$landingSection->slug])
                            </div>
                            @php($alternatingIndex++)
                        @else
                            @include($sectionViews[$landingSection->slug])
                        @endif
                    </div>
                @endif
            @endforeach
        </main>
        <x-landing.footer />
    </div>
    <x-site.telegram-popup />
    <x-site.cookie-consent />
    <x-analytics.yandex-metrika />
</body>
</html>
