<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Авторизация через MAX — Сообщество 24Logist</title>
    <x-site.favicon />
    <x-fonts.preload />
    <script src="https://st.max.ru/js/max-web-app.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="landing-page community-page community-main">
    <div class="landing-shell community-auth-shell">
        <div class="community-auth-card"
             data-max-mini-app
             data-approve-url="{{ route('community.auth.max.approve') }}"
             data-session-url="{{ route('community.auth.max.session') }}">
            <span class="section-kicker">MAX</span>
            <h1>Вход в сообщество</h1>
            <p>Безопасно проверяем ваш аккаунт MAX.</p>
            <a class="community-provider community-provider--max" data-max-return hidden>
                Вернуться в сообщество
            </a>
            <p data-max-mini-status class="community-auth-status">Подтверждаем вход…</p>
        </div>
    </div>
</main>
</body>
</html>
