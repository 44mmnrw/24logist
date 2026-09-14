<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Сообщество 24Logist')</title>
    <meta name="description" content="@yield('description', 'Обсуждения перевозок, электронных документов и цифровой логистики в сообществе 24Logist.')">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <x-site.favicon />
    <x-fonts.preload />
    @stack('structured-data')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="landing-page community-page">
    <header class="community-toolbar">
        <div class="landing-shell community-toolbar__inner">
            <div class="community-toolbar__identity">
                <a class="community-toolbar__logo" href="{{ url('/') }}" aria-label="На главную страницу 24Logist"><x-landing.logo /></a>
                <a class="community-toolbar__brand" href="{{ route('community.index') }}">Сообщество <span>24Logist</span></a>
            </div>
            @auth('community')
                @php($unreadNotifications = auth('community')->user()->communityNotifications()->whereNull('read_at')->count())
                <nav class="community-toolbar__desktop" aria-label="Профиль сообщества">
                    <a href="{{ route('community.notifications') }}">Уведомления@if ($unreadNotifications > 0) ({{ $unreadNotifications }})@endif</a>
                    <a class="community-toolbar__profile" href="{{ route('community.profile', auth('community')->user()) }}"><x-community.avatar :user="auth('community')->user()" size="sm" /><span>{{ auth('community')->user()->displayName() }}</span></a>
                    <a href="{{ route('community.settings') }}">Настройки</a>
                    @if (auth('community')->user()->isModerator())
                        <a href="{{ route('community.moderation.index') }}">Модерация</a>
                    @endif
                    <form method="POST" action="{{ route('community.logout') }}">@csrf<button type="submit">Выйти</button></form>
                </nav>
                <details class="community-toolbar__menu">
                    <summary aria-label="Открыть меню сообщества">
                        <x-community.avatar :user="auth('community')->user()" size="sm" />
                        @if ($unreadNotifications > 0)<span class="community-toolbar__unread" aria-label="{{ $unreadNotifications }} новых уведомлений">{{ $unreadNotifications }}</span>@endif
                        <x-community.icon name="menu-2" size="18" />
                    </summary>
                    <nav aria-label="Мобильное меню сообщества">
                        <a href="{{ route('community.index') }}">Все обсуждения</a>
                        <a href="{{ route('community.notifications') }}">Уведомления@if ($unreadNotifications > 0) ({{ $unreadNotifications }})@endif</a>
                        <a href="{{ route('community.profile', auth('community')->user()) }}">Мой профиль</a>
                        <a href="{{ route('community.settings') }}">Настройки</a>
                        @if (auth('community')->user()->isModerator())
                            <a href="{{ route('community.moderation.index') }}">Модерация</a>
                        @endif
                        <a href="{{ url('/') }}">На главную сайта</a>
                        <form method="POST" action="{{ route('community.logout') }}">@csrf<button type="submit">Выйти</button></form>
                    </nav>
                </details>
            @else
                <a class="btn btn--primary btn--sm community-toolbar__login" href="{{ route('community.login') }}">Войти</a>
            @endauth
        </div>
    </header>

    <main class="community-main">
        @if (session('status'))
            <div class="landing-shell"><div class="community-flash" role="status">{{ session('status') }}</div></div>
        @endif
        @if ($errors->any())
            <div class="landing-shell"><div class="community-errors" role="alert"><strong>Проверьте данные:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
        @endif
        @yield('content')
    </main>
    <x-landing.footer />
</div>
</body>
</html>
