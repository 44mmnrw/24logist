<!DOCTYPE html>
<html lang="ru-RU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Вход в ЛогистРу</title>
    <style nonce="{{ $scriptNonce }}">
        html, body { min-height: 100%; margin: 0; background: #f3f7fc; }
        .handoff-fallback { display: grid; min-height: 100vh; place-items: center; font: 16px/1.5 sans-serif; }
        .handoff-fallback button { padding: 12px 20px; border: 0; border-radius: 12px; background: #1d4ed8; color: #fff; font: inherit; font-weight: 700; }
    </style>
</head>
<body>
    <form id="cabinet-platform-handoff" method="POST" action="{{ $handoffUrl }}" hidden>
        <input type="hidden" name="ticket" value="{{ $ticket }}">
    </form>

    <noscript>
        <main class="handoff-fallback">
            <button type="submit" form="cabinet-platform-handoff">Продолжить в ЛогистРу</button>
        </main>
    </noscript>

    <script nonce="{{ $scriptNonce }}">
        document.getElementById('cabinet-platform-handoff').requestSubmit();
    </script>
</body>
</html>
