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
    <x-landing.header />
    <div class="community-toolbar">
        <div class="landing-shell community-toolbar__inner">
            <a class="community-toolbar__brand" href="{{ route('community.index') }}">Сообщество 24Logist</a>
            <nav aria-label="Профиль сообщества">
                @auth('community')
                    <a href="{{ route('community.notifications') }}">Уведомления</a>
                    <a href="{{ route('community.profile', auth('community')->user()) }}">{{ '@'.auth('community')->user()->username }}</a>
                    <a href="{{ route('community.settings') }}">Настройки</a>
                    @if (auth('community')->user()->isModerator())
                        <a href="{{ route('community.moderation.index') }}">Модерация</a>
                    @endif
                    <form method="POST" action="{{ route('community.logout') }}">@csrf<button type="submit">Выйти</button></form>
                @else
                    <a class="btn btn--primary btn--sm" href="{{ route('community.login') }}">Войти</a>
                @endauth
            </nav>
        </div>
    </div>

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
