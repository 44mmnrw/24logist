<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#153977">
    <title>Связь ЭПД — ЛогистРу</title>
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
        data-sound-success="{{ asset('sounds/epd/success.mp3') }}"
        data-sound-failure="{{ asset('sounds/epd/failure.mp3') }}"
    >
        <header class="epd-game__topbar">
            <div class="epd-game__brand" aria-label="ЛогистРу">
                <span class="epd-game__logo-wrap">
                    <img src="{{ asset('images/logo.svg') }}" alt="ЛогистРу" width="154" height="38">
                </span>
            </div>

            <div class="epd-game__topbar-actions">
                <button class="epd-game__sound" type="button" data-epd-sound aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 5 6.8 8.5H3v7h3.8L11 19V5Zm4.2 3.2a5 5 0 0 1 0 7.6M18 5.5a9 9 0 0 1 0 13"/></svg>
                    <span data-epd-sound-label>Звук включён</span>
                </button>
                <div class="epd-game__topbar-meta">
                    <span class="epd-game__online-dot" aria-hidden="true"></span>
                    <span>Игровой стенд ЭПД</span>
                </div>
            </div>
        </header>

        <main class="epd-game__main">
            <div class="epd-game__shell">
                <header class="epd-game__intro">
                    <div>
                        <span class="epd-game__eyebrow">Проверка совместимости</span>
                        <h1>Установите связь ЭПД</h1>
                        <p>Выберите своего оператора и запустите обмен электронными перевозочными документами.</p>
                    </div>

                    <div class="epd-game__legend" aria-label="Этапы игры">
                        <span><b>1</b> Оператор</span>
                        <i aria-hidden="true"></i>
                        <span><b>2</b> Документы</span>
                        <i aria-hidden="true"></i>
                        <span><b>3</b> Связь</span>
                    </div>
                </header>

                <section class="epd-game__machine" aria-label="Игровой автомат связи ЭПД">
                    <div class="epd-game__beam" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </div>

                    <div class="epd-game__grid">
                        <section class="epd-game__panel epd-game__panel--sender">
                            <div class="epd-game__panel-heading">
                                <span class="epd-game__step epd-game__step--blue">1</span>
                                <div>
                                    <span class="epd-game__caption">Ваш канал</span>
                                    <h2>Оператор ЭПД</h2>
                                </div>
                            </div>

                            <label class="epd-game__field" for="epd-operator">
                                <span>Выберите оператора</span>
                                <span class="epd-game__select-wrap">
                                    <select id="epd-operator" data-epd-operator>
                                        <option value="">Не выбран</option>
                                        <option value="АО «ПФ «СКБ Контур»">АО «ПФ «СКБ Контур»</option>
                                        <option value="АО «Калуга Астрал»">АО «Калуга Астрал»</option>
                                        <option value="ООО «Эдивеб»">ООО «Эдивеб»</option>
                                        <option value="ООО «Такском»">ООО «Такском»</option>
                                        <option value="ООО «СберКорус»">ООО «СберКорус»</option>
                                        <option value="ООО «Компания «Тензор»">ООО «Компания «Тензор»</option>
                                        <option value="ООО «Эвотор ОФД»">ООО «Эвотор ОФД»</option>
                                        <option value="ООО «ФораПром»">ООО «ФораПром»</option>
                                        <option value="ООО «Айтиком»">ООО «Айтиком»</option>
                                        <option value="ООО «ОПЕРАТОР-ЦРПТ»">ООО «ОПЕРАТОР-ЦРПТ»</option>
                                        <option value="АО «Точка»">АО «Точка»</option>
                                        <option value="ООО «НТСсофт»">ООО «НТСсофт»</option>
                                        <option value="АО «Энергетические системы и коммуникации»">АО «Энергетические системы и коммуникации»</option>
                                        <option value="АО «НИИАС»">АО «НИИАС»</option>
                                        <option value="ООО «АТИ-Доки»">ООО «АТИ-Доки»</option>
                                        <option value="АО «НТЦ СТЭК»">АО «НТЦ СТЭК»</option>
                                    </select>
                                    <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 7.5 5 5 5-5"/></svg>
                                </span>
                            </label>

                            <div class="epd-game__identity" data-epd-identity>
                                <span class="epd-game__identity-icon" aria-hidden="true">
                                    <img src="{{ asset('images/icons/epd-sender.svg') }}" alt="" width="28" height="28">
                                </span>
                                <span>
                                    <small>Отправитель</small>
                                    <strong data-epd-selected>Оператор не выбран</strong>
                                </span>
                            </div>
                        </section>

                        <section class="epd-game__panel epd-game__panel--reels" aria-busy="false" data-epd-reels-panel>
                            <div class="epd-game__panel-heading">
                                <span class="epd-game__step epd-game__step--cyan">2</span>
                                <div>
                                    <span class="epd-game__caption">Пакет данных</span>
                                    <h2>Документы</h2>
                                </div>
                            </div>

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

                            <p class="epd-game__hint" data-epd-hint>Барабаны готовы к передаче</p>
                        </section>

                        <section class="epd-game__panel epd-game__panel--receiver" aria-live="polite" aria-atomic="true">
                            <div class="epd-game__panel-heading">
                                <span class="epd-game__step epd-game__step--green">3</span>
                                <div>
                                    <span class="epd-game__caption">Встречный канал</span>
                                    <h2>Результат</h2>
                                </div>
                            </div>

                            <div class="epd-game__result" data-epd-result data-state="idle">
                                <span class="epd-game__result-icon" data-epd-result-icon aria-hidden="true">
                                    <span class="epd-game__route-animation" data-epd-route-animation></span>
                                    <span class="epd-game__route-failure-animation" data-epd-route-failure-animation></span>
                                    <span class="epd-game__route-success-animation" data-epd-route-success-animation></span>
                                </span>
                                <span class="epd-game__result-kicker" data-epd-result-kicker>Ожидание запуска</span>
                                <strong data-epd-result-operator>Оператор появится здесь</strong>
                                <span class="epd-game__status" data-epd-status>Связь не проверена</span>
                            </div>
                        </section>
                    </div>

                    <div class="epd-game__controls">
                        <button class="epd-game__spin" type="button" data-epd-spin disabled>
                            <span class="epd-game__spin-icon" aria-hidden="true">
                                <span class="epd-game__retry-animation" data-epd-retry-animation></span>
                            </span>
                            <span data-epd-spin-label>Установить связь</span>
                        </button>
                        <p data-epd-control-hint>Сначала выберите своего оператора ЭПД</p>
                    </div>
                </section>

                <p class="epd-game__disclaimer">Игра создана в развлекательных целях. Результаты игровой проверки формируются случайным образом и не отражают фактическую совместимость операторов.</p>
            </div>
        </main>
    </div>
</body>
</html>
