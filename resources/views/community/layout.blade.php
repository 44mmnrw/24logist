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
    @vite(['resources/css/community.css', 'resources/js/app.js'])
</head>
<body>
<div class="landing-page community-page">
    <header class="community-toolbar">
        <div class="landing-shell community-toolbar__inner">
            <div class="community-toolbar__identity">
                <a class="community-toolbar__logo" href="{{ url('/') }}" aria-label="На главную страницу 24Logist"><x-landing.logo /></a>
                <a class="community-toolbar__brand" href="{{ route('community.index') }}">Общение</a>
            </div>
            @php($searchAction = request()->routeIs('community.categories.show') ? route('community.categories.show', request()->route('category')) : route('community.index'))
            @php($headerSearch = is_string(request()->query('q')) ? mb_substr(trim(request()->query('q')), 0, 100) : '')
            <form class="community-search community-toolbar__search" method="GET" action="{{ $searchAction }}" role="search" data-community-search>
                <label for="community-search-input" class="sr-only">Поиск по темам</label>
                <input id="community-search-input" type="search" name="q" value="{{ $headerSearch }}" maxlength="100" placeholder="Найти тему или ответ" autocomplete="off" enterkeyhint="search">
                <button class="community-toolbar__search-clear" type="button" aria-label="Очистить поиск" data-community-search-clear data-reset-url="{{ $searchAction }}" @if ($headerSearch === '') hidden @endif><x-community.icon name="x" size="18" /></button>
            </form>
            @auth('community')
                @php($unreadNotifications = auth('community')->user()->communityNotifications()->whereNull('read_at')->count())
                <div class="community-toolbar__actions">
                    <a class="community-toolbar__notifications" href="{{ route('community.notifications') }}" aria-label="Уведомления{{ $unreadNotifications > 0 ? ', новых: '.$unreadNotifications : '' }}">
                        <x-community.icon name="bell" size="19" />
                        @if ($unreadNotifications > 0)<span class="community-toolbar__unread" aria-hidden="true">{{ $unreadNotifications }}</span>@endif
                    </a>
                    <details class="community-toolbar__menu">
                        <summary aria-label="Открыть меню профиля">
                            <x-community.avatar :user="auth('community')->user()" size="sm" />
                            <x-community.icon name="chevron-down" size="16" />
                        </summary>
                        <nav aria-label="Меню профиля">
                            @if (auth('community')->user()->isOnboarded())
                                <a href="{{ route('community.profile', auth('community')->user()) }}">Мой профиль</a>
                                <a href="{{ route('community.settings') }}">Настройки</a>
                                @if (auth('community')->user()->isModerator())
                                    <a href="{{ route('community.moderation.index') }}">Модерация</a>
                                @endif
                            @else
                                <a href="{{ route('community.onboarding') }}">Завершить регистрацию</a>
                            @endif
                            <form method="POST" action="{{ route('community.logout') }}">@csrf<button type="submit">Выйти</button></form>
                        </nav>
                    </details>
                    <a class="btn btn--sm community-toolbar__post" href="{{ route('community.posts.create') }}"><x-community.icon name="message-plus" size="18" /><span class="community-toolbar__post-label">Создать</span></a>
                </div>
            @else
                <div class="community-toolbar__actions community-toolbar__guest">
                    <a class="btn btn--ghost btn--sm" href="{{ route('community.login') }}">Войти</a>
                    <a class="btn btn--primary btn--sm" href="{{ route('community.register') }}">Зарегистрироваться</a>
                </div>
            @endauth
        </div>
    </header>

    <main class="community-main">
        @yield('content')
    </main>

    <div class="community-toasts" data-community-toasts aria-live="polite" aria-atomic="false">
        @if (session('status'))
            <div class="community-toast community-toast--success" data-community-toast data-timeout="5000" role="status">
                <span class="community-toast__icon" aria-hidden="true">✓</span>
                <div class="community-toast__content"><strong>Готово</strong><p>{{ session('status') }}</p></div>
                <button class="community-toast__close" type="button" data-community-toast-close aria-label="Закрыть"><x-community.icon name="x" size="17" /></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="community-toast community-toast--danger" data-community-toast data-timeout="8000" role="alert">
                <span class="community-toast__icon" aria-hidden="true">!</span>
                <div class="community-toast__content"><strong>Проверьте данные</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                <button class="community-toast__close" type="button" data-community-toast-close aria-label="Закрыть"><x-community.icon name="x" size="17" /></button>
            </div>
        @endif
    </div>
    <div class="community-tooltip" id="community-tooltip" data-community-tooltip role="tooltip" hidden></div>
</div>
</body>
</html>
