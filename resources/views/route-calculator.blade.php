<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Карта и расчёт маршрута — ЛогистРу</title>
    <meta name="description" content="Постройте автомобильный или грузовой маршрут, оцените расстояние, время и стоимость перевозки.">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="{{ route('route-calculator.index') }}">
    <x-site.favicon />
    <x-fonts.preload />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="landing-page route-calculator-page">
    <x-landing.header />
    <main class="community-main">
    <div class="landing-shell">
        <div
            class="route-calculator-tool"
            data-route-calculator-calculator
            data-city-suggest-url="{{ route('route-calculator.cities') }}"
            data-calculate-url="{{ route('route-calculator.calculate') }}"
        >
            <header class="route-calculator-tool__header">
                <div>
                    <a class="community-back" href="{{ url('/') }}">← На главную</a>
                    <h1>Карта и расчёт маршрута</h1>
                    <p>Укажите пункты отправления и назначения — ЛогистРу рассчитает дорогу на платформе.</p>
                </div>
            </header>

            <div class="route-calculator-tool__workspace">
                <aside class="route-calculator-panel">
                    <form class="route-calculator-form" data-route-form autocomplete="off">
                        <label class="route-calculator-field">
                            <span>Откуда</span>
                            <input type="text" name="from_city" placeholder="Например, Москва" maxlength="120" required data-route-city="from">
                            <span class="route-calculator-suggest" data-route-suggest="from" hidden></span>
                        </label>

                        <label class="route-calculator-field">
                            <span>Куда</span>
                            <input type="text" name="to_city" placeholder="Например, Казань" maxlength="120" required data-route-city="to">
                            <span class="route-calculator-suggest" data-route-suggest="to" hidden></span>
                        </label>

                        <div class="route-calculator-fields-row">
                            <label class="route-calculator-field">
                                <span>Стоимость, ₽/км</span>
                                <input type="number" name="price_per_km" min="0" max="999999" step="0.01" inputmode="decimal" placeholder="Необязательно">
                            </label>
                            <label class="route-calculator-field">
                                <span>Тип маршрута</span>
                                <select name="routing_profile" data-route-profile>
                                    <option value="car">Легковой</option>
                                    <option value="truck">Грузовой</option>
                                </select>
                            </label>
                        </div>

                        <label class="route-calculator-field">
                            <span>Платные дороги</span>
                            <select name="toll_mode">
                                <option value="prefer_toll">Можно использовать</option>
                                <option value="avoid_toll">По возможности избегать</option>
                            </select>
                        </label>

                        <fieldset class="route-calculator-truck" data-route-truck hidden>
                            <legend>Параметры грузовика</legend>
                            <div class="route-calculator-fields-row">
                                <label class="route-calculator-field"><span>Полная масса, т</span><input type="number" name="truck[gross_weight_t]" min="0.001" max="500" step="0.001"></label>
                                <label class="route-calculator-field"><span>Нагрузка на ось, т</span><input type="number" name="truck[max_axle_load_t]" min="0.001" max="100" step="0.001"></label>
                                <label class="route-calculator-field"><span>Высота, м</span><input type="number" name="truck[height_m]" min="0.001" max="20" step="0.001"></label>
                                <label class="route-calculator-field"><span>Ширина, м</span><input type="number" name="truck[width_m]" min="0.001" max="20" step="0.001"></label>
                                <label class="route-calculator-field"><span>Длина, м</span><input type="number" name="truck[length_m]" min="0.001" max="100" step="0.001"></label>
                                <label class="route-calculator-field"><span>Количество осей</span><input type="number" name="truck[axle_count]" min="1" max="32" step="1"></label>
                            </div>
                            <label class="route-calculator-check"><input type="checkbox" name="truck[hazmat]" value="1"><span>Опасный груз</span></label>
                        </fieldset>

                        <details class="route-calculator-details">
                            <summary>Расчёт времени водителя</summary>
                            <div class="route-calculator-fields-row">
                                <label class="route-calculator-field"><span>Рабочий день, ч</span><input type="number" name="driver_work_hours_per_day" value="8" min="0.5" max="24" step="0.5"></label>
                                <label class="route-calculator-field"><span>Пробег в день, км</span><input type="number" name="max_km_per_day" value="600" min="1" max="2000" step="1"></label>
                            </div>
                        </details>

                        <div class="route-calculator-form__actions">
                            <button class="btn btn--primary" type="submit" data-route-submit>Рассчитать</button>
                            <button class="btn btn--outline" type="reset" data-route-reset>Очистить</button>
                        </div>

                        <p class="route-calculator-status" role="status" data-route-status hidden></p>
                    </form>

                    <section class="route-calculator-result" aria-live="polite" data-route-result hidden>
                        <h2>Результат</h2>
                        <dl>
                            <div data-result-row="distance"><dt>Расстояние</dt><dd data-result-value="distance"></dd></div>
                            <div data-result-row="time"><dt>Время в пути</dt><dd data-result-value="time"></dd></div>
                            <div data-result-row="freight"><dt>Стоимость перевозки</dt><dd data-result-value="freight"></dd></div>
                            <div data-result-row="toll"><dt>Платные участки</dt><dd data-result-value="toll"></dd></div>
                            <div class="is-total" data-result-row="total"><dt>Итого</dt><dd data-result-value="total"></dd></div>
                        </dl>
                        <div class="route-calculator-warnings" data-route-warnings hidden>
                            <strong>Обратите внимание</strong>
                            <ul></ul>
                        </div>
                    </section>
                </aside>

                <div class="route-calculator-map-wrap">
                    <div class="route-calculator-map" data-route-map aria-label="Карта рассчитанного маршрута"></div>
                </div>
            </div>
            <p class="route-calculator-tool__attribution">
                © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>
            </p>
        </div>
    </div>
    </main>
    <x-landing.footer />
</div>
</body>
</html>

