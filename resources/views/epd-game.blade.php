@php
    $epdOperators = [
        'ПФ СКБ Контур',
        'Калуга Астрал',
        'Эдивеб',
        'Такском',
        'СберКорус',
        'Компания Тензор',
        'Эвотор ОФД',
        'ФораПром',
        'Айтиком',
        'ОПЕРАТОР-ЦРПТ',
        'Точка',
        'НТСсофт',
        'Энергетические системы и коммуникации',
        'НИИАС',
        'АТИ-Доки',
        'НТЦ СТЭК',
    ];
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f4f7fb">
    <title>Рулетка роуминга ЭПД</title>
    <meta name="description" content="Игровая проверка связи между операторами электронных перевозочных документов.">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="epd-game-page">
    <div
        class="epd-game"
        data-epd-game
        data-sound-pull="{{ asset('sounds/epd/slot-pull.mp3') }}"
        data-sound-stop="{{ asset('sounds/epd/reel-stop.mp3') }}"
        data-sound-success="{{ asset('sounds/epd/success-win31.mp3') }}"
        data-sound-failure="{{ asset('sounds/epd/failure.mp3') }}"
    >
        <header class="epd-game__topbar">
            <a class="epd-game__brand" href="{{ url('/') }}" aria-label="ЛогистРу — перейти на главную">
                <span class="epd-game__logo-wrap">
                    <img src="{{ asset('images/logo.svg') }}" alt="ЛогистРу" width="154" height="38">
                </span>
                <span class="epd-game__brand-meta">Игровой стенд ЭПД</span>
            </a>

            <div class="epd-game__topbar-actions">
                <button class="epd-game__sound" type="button" data-epd-sound aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5 6.8 8.5H3v7h3.8L11 19V5Zm4.2 3.2a5 5 0 0 1 0 7.6M18 5.5a9 9 0 0 1 0 13"/></svg>
                    <span data-epd-sound-label>Звук вкл.</span>
                </button>
            </div>
        </header>

        <main class="epd-game__main">
            <div class="epd-game__shell">
                <header class="epd-game__intro">
                    <div>
                        <h1>Рулетка роуминга ЭПД</h1>
                        <p>Выберите операторов и проложите защищённый цифровой маршрут обмена электронными перевозочными документами.</p>
                    </div>
                </header>

                <section class="epd-game__machine" aria-label="Игровой автомат связи ЭПД">
                    <div class="epd-game__route" data-epd-route aria-hidden="true">
                        <span class="epd-game__route-segment epd-game__route-segment--out"></span>
                        <span class="epd-game__route-node epd-game__route-node--sender"></span>
                        <span class="epd-game__route-node epd-game__route-node--core"></span>
                        <span class="epd-game__route-node epd-game__route-node--receiver"></span>
                        <span class="epd-game__route-segment epd-game__route-segment--in"></span>
                    </div>

                    <div class="epd-game__grid">
                        <section class="epd-game__node epd-game__node--sender">
                            <label class="epd-game__field" for="epd-operator">
                                <span class="epd-game__field-label">Выберите отправителя</span>
                                <span class="epd-game__select-wrap">
                                    <select id="epd-operator" data-epd-operator>
                                        <option value="">Не выбран</option>
                                        @foreach ($epdOperators as $epdOperator)
                                            <option value="{{ $epdOperator }}">{{ $epdOperator }}</option>
                                        @endforeach
                                    </select>
                                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 7.5 5 5 5-5"/></svg>
                                </span>
                            </label>
                        </section>

                        <section class="epd-game__core" aria-busy="false" data-epd-reels-panel>
                            <div class="epd-game__core-heading">
                                <h2>Барабаны документов ЭПД</h2>
                                <div class="epd-game__core-meta">
                                    <span class="epd-game__route-indicator" aria-hidden="true">
                                        <span class="epd-game__route-animation" data-epd-route-animation></span>
                                    </span>
                                    <span class="epd-game__core-state" data-epd-core-state>Готово к проверке</span>
                                </div>
                            </div>

                            <div class="epd-game__slot-machine">
                                <img class="epd-game__slot-frame" src="{{ asset('images/icons/epd-slot-frame.svg') }}" alt="" aria-hidden="true">
                                <span class="epd-game__slot-lights" aria-hidden="true">
                                    <i></i><i></i><i></i><i></i><i></i>
                                </span>
                                <div class="epd-game__reels" aria-label="Барабаны с типами документов">
                                    <div class="epd-game__reel" data-epd-reel>
                                        <div class="epd-game__reel-track" data-epd-reel-track>
                                            <span class="epd-game__reel-item">
                                                <b class="epd-game__reel-label">ЭТрН</b>
                                                <small>накладная</small>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="epd-game__reel" data-epd-reel>
                                        <div class="epd-game__reel-track" data-epd-reel-track>
                                            <span class="epd-game__reel-item">
                                                <b class="epd-game__reel-label">ЭЗЗ</b>
                                                <small>заказ-заявка</small>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="epd-game__reel" data-epd-reel>
                                        <div class="epd-game__reel-track" data-epd-reel-track>
                                            <span class="epd-game__reel-item">
                                                <b class="epd-game__reel-label">ЭПЭ</b>
                                                <small>поручение</small>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="epd-game__hint" data-epd-hint>Барабаны готовы к передаче</p>
                        </section>

                        <section class="epd-game__node epd-game__node--receiver">
                            <label class="epd-game__field" for="epd-target-operator">
                                <span class="epd-game__field-label">Выберите получателя</span>
                                <span class="epd-game__select-wrap">
                                    <select id="epd-target-operator" data-epd-target-operator>
                                        <option value="">Не выбран</option>
                                        @foreach ($epdOperators as $epdOperator)
                                            <option value="{{ $epdOperator }}">{{ $epdOperator }}</option>
                                        @endforeach
                                    </select>
                                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 7.5 5 5 5-5"/></svg>
                                </span>
                            </label>
                        </section>

                        <div class="epd-game__controls">
                            <button class="epd-game__spin" type="button" data-epd-spin disabled>
                                <span class="epd-game__spin-icon" aria-hidden="true">
                                    <span class="epd-game__retry-animation" data-epd-retry-animation></span>
                                </span>
                                <span data-epd-spin-label>Испытать удачу</span>
                            </button>
                            <p data-epd-control-hint>Сначала выберите своего оператора ЭПД</p>
                        </div>
                    </div>

                    <div class="epd-game__feedback" data-epd-result data-state="idle">
                        <div class="epd-game__celebration" data-epd-celebration aria-hidden="true">
                            <canvas class="epd-game__confetti" data-epd-confetti></canvas>
                        </div>

                        <p class="epd-game__sr-only" data-epd-announcement aria-live="polite" aria-atomic="true">Связь не проверена</p>
                    </div>

                    <div class="epd-game__outcome-overlay" data-epd-outcome-dialog data-state="idle" data-visible="false" aria-hidden="true">
                        <div class="epd-game__outcome-dialog" role="status" aria-live="assertive" aria-atomic="true">
                            <div class="epd-game__outcome-dialog-icon" aria-hidden="true">
                                <span class="epd-game__route-success-animation" data-epd-route-success-animation></span>
                                <span class="epd-game__route-failure-animation" data-epd-route-failure-animation></span>
                            </div>
                            <span class="epd-game__result-kicker" data-epd-result-kicker>Ожидание запуска</span>
                            <div class="epd-game__result-route" aria-label="Маршрут между операторами">
                                <p>
                                    <span>Отправитель:</span>
                                    <strong data-epd-result-sender>Оператор не выбран</strong>
                                </p>
                                <span class="epd-game__result-route-arrow" aria-hidden="true">→</span>
                                <p>
                                    <span>Получатель:</span>
                                    <strong id="epd-outcome-title" data-epd-result-operator>Оператор не выбран</strong>
                                </p>
                            </div>
                            <span class="epd-game__status" data-epd-status>Связь не проверена</span>
                        </div>
                    </div>
                </section>

                <p class="epd-game__disclaimer">Игра создана в развлекательных целях. Результаты игровой проверки формируются случайным образом и не отражают фактическую совместимость операторов. Все совпадения случайны.</p>
            </div>
        </main>
    </div>
</body>
</html>
