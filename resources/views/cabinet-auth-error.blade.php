<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Не удалось открыть ЛогистРу</title>
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css'])
</head>
<body class="cabinet-auth-transition">
    <main class="cabinet-auth-transition__card" aria-labelledby="handoff-error-title">
        <span class="cabinet-login-modal__eyebrow">ЛогистРу</span>
        <h1 id="handoff-error-title">Переход не выполнен</h1>
        <p>{{ $message }}</p>
        <a class="btn btn--primary" href="{{ url('/') }}">Вернуться на лендинг</a>
    </main>
</body>
</html>
