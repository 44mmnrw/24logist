@php
    $title = 'Куда уйдёт ЭТрН? — рулетка роуминга';
    $description = 'Интерактивная игра по мотивам рулетки роуминга ЭПД: запустите барабаны и узнайте, какому оператору выпадет ЭТрН.';
    $canonical = route('etrn-roulette');
    $communityUser = auth('community')->user();
    $roulettePlayer = $communityUser?->etrnRoulettePlayer;
    $communitySettings = $communityUser === null ? app(\App\Services\SiteSettingsService::class) : null;
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f5faff">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="etrn-roulette-page etrn-roulette-page--game">
    <main class="etrn-roulette" data-etrn-game data-attempts-url="{{ route('etrn-roulette.attempts.index') }}" data-attempts-increment-url="{{ route('etrn-roulette.attempts.store') }}" style="--etrn-poster-url: url('{{ asset('images/etrn-game/poster.webp') }}'); --etrn-kontur-url: url('{{ asset('images/etrn-game/kontur.png') }}'); --etrn-astral-url: url('{{ asset('images/etrn-game/astral.svg') }}'); --etrn-saby-url: url('{{ asset('images/etrn-game/saby.svg') }}'); --etrn-ediveb-url: url('{{ asset('images/etrn-game/ediveb.png') }}'); --etrn-evotor-url: url('{{ asset('images/etrn-game/evotor.svg') }}'); --etrn-taxcom-url: url('{{ asset('images/etrn-game/taxcom.png') }}'); --etrn-sber-url: url('{{ asset('images/etrn-game/sber.png') }}'); --etrn-tochka-url: url('{{ asset('images/etrn-game/tochka.svg') }}'); --etrn-mig-url: url('{{ asset('images/etrn-game/mig.svg') }}'); --etrn-itcom-url: url('{{ asset('images/etrn-game/itcom.svg') }}'); --etrn-stek-url: url('{{ asset('images/etrn-game/stek.svg') }}'); --etrn-oneofd-url: url('{{ asset('images/etrn-game/1ofd.svg') }}'); --etrn-atidoki-url: url('{{ asset('images/etrn-game/ati.webp') }}'); --etrn-niias-url: url('{{ asset('images/etrn-game/niias.png') }}'); --etrn-crpt-url: url('{{ asset('images/etrn-game/crpt.svg') }}'); --etrn-logistru-url: url('{{ asset('images/etrn-game/logistru.png') }}')"
        aria-label="Развлекательная рулетка роуминга ЭПД. Результат случайный и не отражает фактическую совместимость операторов."
        data-sound-pull="{{ asset('sounds/epd/slot-pull.mp3') }}"
        data-sound-stop="{{ asset('sounds/epd/reel-stop.mp3') }}"
        data-sound-success="{{ asset('sounds/epd/success-win31.mp3') }}"
        data-sound-failure="{{ asset('sounds/epd/failure.mp3') }}">
        <script type="application/json" data-etrn-operators>@json(config('epd_operators'))</script>
        <div class="etrn-roulette__poster">
            <img class="etrn-roulette__art" src="{{ asset('images/etrn-game/poster-blank-reels.webp') }}" alt="Логист запускает рулетку роуминга ЭПД" width="1086" height="1448">
            <img class="etrn-roulette__pulled" src="{{ asset('images/etrn-game/poster-lever-pulled-blank-reels.webp') }}" alt="" aria-hidden="true" width="1086" height="1448">
            <canvas class="etrn-roulette__confetti" data-epd-confetti aria-hidden="true"></canvas>

            <div class="etrn-roulette__reels" aria-label="Три барабана с операторами ЭДО" aria-busy="false" data-etrn-reels>
                <div class="etrn-roulette__reel" data-etrn-reel><div class="etrn-roulette__track" data-etrn-track></div></div>
                <div class="etrn-roulette__reel" data-etrn-reel><div class="etrn-roulette__track" data-etrn-track></div></div>
                <div class="etrn-roulette__reel" data-etrn-reel><div class="etrn-roulette__track" data-etrn-track></div></div>
            </div>

            <div class="etrn-roulette__display" role="status" aria-live="polite" aria-atomic="true">
                <span data-etrn-status>Куда отправится</span>
                <strong data-etrn-result>ваша ЭТрН?</strong>
            </div>

            <button class="etrn-roulette__lever" type="button" data-etrn-lever aria-label="Потянуть ручку и запустить барабаны"></button>
            <div class="etrn-roulette__attempts" aria-live="polite">
                <span class="etrn-roulette__attempts-row">
                    <span class="etrn-roulette__attempts-label">Попытки</span>
                    <strong data-etrn-attempt-count>00000</strong>
                </span>
                <span class="etrn-roulette__attempts-row">
                    <span class="etrn-roulette__attempts-label">Супербонус</span>
                    <strong data-etrn-jackpot-count>0</strong>
                </span>
            </div>
            <div class="etrn-roulette__prize">
                @if ($roulettePlayer)
                    <span class="etrn-roulette__prize-active">За приз: <strong data-etrn-player-attempt-count>{{ number_format($roulettePlayer->attempts, 0, ',', ' ') }}</strong></span>
                @elseif ($communityUser)
                    <a class="etrn-roulette__prize-link" href="{{ route('etrn-roulette.prize.join') }}">Играть за приз</a>
                @else
                    <a class="etrn-roulette__prize-link" href="{{ route('etrn-roulette.prize.join') }}" data-etrn-auth-open>Играть за приз</a>
                @endif
                <a class="etrn-roulette__rules-link" href="{{ route('etrn-roulette.rules') }}">Правила игры</a>
            </div>
            <button class="etrn-roulette__spin" type="button" data-etrn-spin>
                <span class="etrn-roulette__spin-face">
                    <span data-etrn-spin-label>Испытать удачу</span>
                </span>
            </button>
        </div>

        <div class="etrn-roulette__captcha">
            <x-site.smartcaptcha form="etrn_roulette" invisible />
        </div>

        @if ($communityUser === null)
            <dialog class="etrn-roulette__auth-dialog" data-etrn-auth-dialog aria-labelledby="etrn-auth-title">
                <div class="etrn-roulette__auth-card">
                    <button class="etrn-roulette__auth-close" type="button" data-etrn-auth-close aria-label="Закрыть">×</button>
                    <span class="etrn-roulette__auth-kicker">Розыгрыш подписки</span>
                    <h2 id="etrn-auth-title">Войдите, чтобы играть за приз</h2>
                    <p>Выберите удобный способ входа. После авторизации вы вернётесь в игру, а следующие попытки будут участвовать в розыгрыше. Участие не гарантирует получение приза.</p>
                    <div class="etrn-roulette__auth-providers">
                        @if ($communitySettings->communityTelegramEnabled())
                            <a class="etrn-roulette__auth-provider etrn-roulette__auth-provider--telegram" href="{{ route('etrn-roulette.prize.auth', ['provider' => 'telegram']) }}">Продолжить через Telegram</a>
                        @else
                            <span class="etrn-roulette__auth-provider is-disabled">Telegram — скоро</span>
                        @endif
                        @if ($communitySettings->communityVkEnabled())
                            <a class="etrn-roulette__auth-provider etrn-roulette__auth-provider--vk" href="{{ route('etrn-roulette.prize.auth', ['provider' => 'vk']) }}">Продолжить через VK ID</a>
                        @else
                            <span class="etrn-roulette__auth-provider is-disabled">VK ID — скоро</span>
                        @endif
                        @if ($communitySettings->communityMaxEnabled())
                            <a class="etrn-roulette__auth-provider etrn-roulette__auth-provider--max" href="{{ route('etrn-roulette.prize.auth', ['provider' => 'max']) }}">Продолжить через MAX</a>
                        @else
                            <span class="etrn-roulette__auth-provider is-disabled">MAX — скоро</span>
                        @endif
                    </div>
                    <small>Продолжая, вы соглашаетесь с <a href="{{ route('community.rules') }}">правилами сообщества</a> и <a href="{{ route('community.privacy') }}">политикой конфиденциальности</a>.</small>
                </div>
            </dialog>
        @endif
    </main>
    <x-site.tracking />
</body>
</html>
