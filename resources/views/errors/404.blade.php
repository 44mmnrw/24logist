<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-csrf-meta />
    @php
        $og = \App\Support\OpenGraph::forNotFound();
    @endphp
    <title>{{ $og['title'] }}</title>
    <x-seo.open-graph :not-found="true" />
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-landing.sprite />

    <div class="landing-page not-found-page">
        <x-landing.header />

        <main class="not-found-page__main">
            <div class="landing-shell not-found-page__shell">
                <div class="not-found-page__card">
                    <div class="not-found-page__hero" aria-hidden="true">
                        <p class="not-found-page__watermark">404</p>
                        <div class="not-found-page__hero-content">
                            <div class="not-found-page__icon-wrap">
                                <x-landing.icon name="truck" class="not-found-page__truck-icon" />
                            </div>
                            <div class="not-found-page__route-badge">
                                <x-landing.icon name="rotes" class="not-found-page__route-icon" />
                                <span>Маршрут не найден</span>
                            </div>
                        </div>
                    </div>

                    <h1 class="not-found-page__title">Страница заблудилась в пути</h1>

                    <p class="not-found-page__desc">
                        Запрошенный адрес не существует или был перемещён.
                        Вернитесь на главную — там все маршруты на месте.
                    </p>

                    <div class="not-found-page__error-pill">
                        <x-landing.icon name="info-circle" class="not-found-page__error-icon" />
                        <span>Код ошибки: 404 Page Not Found</span>
                    </div>

                    <div class="not-found-page__actions">
                        <a href="{{ url('/') }}" class="btn btn--primary">
                            <x-landing.icon name="home" />
                            На главную
                        </a>
                        <a href="{{ url('/pages/contacts') }}" class="btn btn--ghost not-found-page__btn-secondary">
                            <x-landing.icon name="phone" />
                            Связаться с нами
                        </a>
                    </div>

                    <nav class="not-found-page__quick-links" aria-label="Быстрая навигация">
                        <span class="not-found-page__quick-label">Попробуйте перейти в:</span>
                        <div class="not-found-page__quick-list">
                            <a href="{{ \App\Support\LandingLinks::resolve('#features') }}">
                                <x-landing.icon name="arrow-right" class="not-found-page__link-arrow" />
                                Возможности
                            </a>
                            <a href="{{ \App\Support\LandingLinks::resolve('#pricing') }}">
                                <x-landing.icon name="arrow-right" class="not-found-page__link-arrow" />
                                Тарифы
                            </a>
                            <a href="{{ url('/pages/contacts') }}">
                                <x-landing.icon name="arrow-right" class="not-found-page__link-arrow" />
                                Контакты
                            </a>
                        </div>
                    </nav>

                    <div class="not-found-page__route-bar" aria-hidden="true">
                        @for ($i = 0; $i < 7; $i++)
                            <span></span>
                        @endfor
                    </div>
                </div>
            </div>
        </main>

        <x-landing.footer />
    </div>
    <x-analytics.yandex-metrika />
</body>
</html>
