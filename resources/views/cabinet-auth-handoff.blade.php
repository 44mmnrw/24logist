<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Переход в ЛогистРу</title>
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/platform-handoff.js'])
</head>
<body class="cabinet-auth-transition">
    <main class="cabinet-auth-transition__card" aria-labelledby="handoff-title">
        <span class="cabinet-login-modal__eyebrow">ЛогистРу</span>
        <h1 id="handoff-title">Открываем личный кабинет</h1>
        <p>Безопасно завершаем вход. Это займёт несколько секунд.</p>

        <form method="POST" action="{{ $handoffUrl }}" data-platform-handoff-form>
            <input type="hidden" name="ticket" value="{{ $ticket }}">
            <button class="cabinet-login-modal__submit" type="submit">Продолжить в ЛогистРу</button>
        </form>

        <noscript>
            <p>Для автоматического перехода включите JavaScript или нажмите кнопку выше.</p>
        </noscript>
    </main>
</body>
</html>
