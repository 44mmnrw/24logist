@php
    $title = 'Куда уйдёт ЭТрН? — рулетка роуминга';
    $description = 'Интерактивная игра по мотивам рулетки роуминга ЭПД: запустите барабаны и узнайте, какому оператору выпадет ЭТрН.';
    $canonical = route('etrn-roulette');
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5faff">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="etrn-roulette-page">
    <main class="etrn-roulette" data-etrn-game style="--etrn-poster-url: url('{{ asset('images/etrn-game/poster.webp') }}'); --etrn-kontur-url: url('{{ asset('images/etrn-game/kontur.png') }}'); --etrn-astral-url: url('{{ asset('images/etrn-game/astral.svg') }}'); --etrn-saby-url: url('{{ asset('images/etrn-game/saby.svg') }}'); --etrn-ediveb-url: url('{{ asset('images/etrn-game/ediveb.png') }}'); --etrn-evotor-url: url('{{ asset('images/etrn-game/evotor.svg') }}'); --etrn-taxcom-url: url('{{ asset('images/etrn-game/taxcom.png') }}'); --etrn-logistru-url: url('{{ asset('images/etrn-game/logistru.png') }}')"
        aria-label="Развлекательная рулетка роуминга ЭПД. Результат случайный и не отражает фактическую совместимость операторов."
        data-sound-pull="{{ asset('sounds/epd/slot-pull.mp3') }}"
        data-sound-stop="{{ asset('sounds/epd/reel-stop.mp3') }}"
        data-sound-success="{{ asset('sounds/epd/success-win31.mp3') }}"
        data-sound-failure="{{ asset('sounds/epd/failure.mp3') }}">
        <script type="application/json" data-etrn-operators>@json(config('epd_operators'))</script>
        <div class="etrn-roulette__poster">
            <img class="etrn-roulette__art" src="{{ asset('images/etrn-game/poster-blank-reels.webp') }}" alt="Логист запускает рулетку роуминга ЭПД" width="1086" height="1448">
            <img class="etrn-roulette__pulled" src="{{ asset('images/etrn-game/poster-lever-pulled-blank-reels.webp') }}" alt="" aria-hidden="true" width="1086" height="1448">

            <div class="etrn-roulette__reels" aria-label="Три барабана с операторами ЭДО" aria-busy="false" data-etrn-reels>
                <div class="etrn-roulette__reel" data-etrn-reel><div class="etrn-roulette__track" data-etrn-track></div></div>
                <div class="etrn-roulette__reel" data-etrn-reel><div class="etrn-roulette__track" data-etrn-track></div></div>
                <div class="etrn-roulette__reel" data-etrn-reel><div class="etrn-roulette__track" data-etrn-track></div></div>
            </div>

            <div class="etrn-roulette__display" role="status" aria-live="polite" aria-atomic="true">
                <span data-etrn-status>3 одинаковых — выигрыш</span>
                <strong data-etrn-result>2 + ЛогистРу — бонус</strong>
            </div>

            <button class="etrn-roulette__lever" type="button" data-etrn-lever aria-label="Потянуть ручку и запустить барабаны"></button>
            <button class="etrn-roulette__spin" type="button" data-etrn-spin>
                <span class="etrn-roulette__spin-face">
                    <svg class="etrn-roulette__spin-icon" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                        <rect x="8" y="22" width="32" height="32" rx="5" transform="rotate(-15 8 22)" stroke="currentColor" stroke-width="4"/>
                        <circle cx="15" cy="29" r="2.5" fill="currentColor"/>
                        <circle cx="24" cy="38" r="2.5" fill="currentColor"/>
                        <circle cx="32" cy="47" r="2.5" fill="currentColor"/>
                        <rect x="32" y="7" width="27" height="27" rx="5" transform="rotate(14 32 7)" stroke="currentColor" stroke-width="4"/>
                        <circle cx="43" cy="17" r="2.5" fill="currentColor"/>
                        <circle cx="51" cy="25" r="2.5" fill="currentColor"/>
                    </svg>
                    <span data-etrn-spin-label>Испытать удачу</span>
                </span>
            </button>
        </div>
    </main>
</body>
</html>
