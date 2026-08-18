@php
    $section = $landing->section('growth');
    $extra = $section?->extra ?? [];
    $defaultCustomerNames = [
        'ООО "ГК «ЛОГОС»"',
        'АО "УКЗ"',
        'ООО "БУГУЛЬМИНСКИЙ СЕЛЬСКОХОЗЯЙСТВЕННЫЙ РЫНОК"',
        'ООО "МЕТАЛЛИНВЕСТСПБ"',
        'ООО "КЛИМАТ-КОМПЛЕКС"',
    ];
    $editedCustomerNames = collect($extra['customer_names'] ?? [])
        ->map(fn ($item) => trim((string) ($item['name'] ?? '')))
        ->values()
        ->all();
    $customerNames = array_replace($defaultCustomerNames, array_filter($editedCustomerNames));
    $marginSegments = [
        ['label' => 'от 5% до 9%', 'value' => '16.9%', 'count' => '10', 'class' => 'growth-dot--blue'],
        ['label' => 'от 10% до 15%', 'value' => '16.9%', 'count' => '10', 'class' => 'growth-dot--green'],
        ['label' => 'от 16%', 'value' => '55.9%', 'count' => '33', 'class' => 'growth-dot--orange'],
        ['label' => 'Вне диапазонов', 'value' => '10.2%', 'count' => '6', 'class' => 'growth-dot--gray'],
    ];
    $customers = [
        ['name' => $customerNames[0], 'value' => 9, 'width' => 100],
        ['name' => $customerNames[1], 'value' => 7, 'width' => 78],
        ['name' => $customerNames[2], 'value' => 4, 'width' => 44],
        ['name' => $customerNames[3], 'value' => 4, 'width' => 44],
        ['name' => $customerNames[4], 'value' => 4, 'width' => 44],
    ];
    $customerViews = [
        'count' => $customers,
        'revenue' => [
            ['name' => $customerNames[0], 'value' => '1,8 млн ₽', 'width' => 100],
            ['name' => $customerNames[1], 'value' => '1,4 млн ₽', 'width' => 78],
            ['name' => $customerNames[3], 'value' => '980 тыс. ₽', 'width' => 54],
            ['name' => $customerNames[4], 'value' => '760 тыс. ₽', 'width' => 42],
            ['name' => $customerNames[2], 'value' => '640 тыс. ₽', 'width' => 36],
        ],
        'margin' => [
            ['name' => $customerNames[4], 'value' => '18,4%', 'width' => 100],
            ['name' => $customerNames[0], 'value' => '16,9%', 'width' => 92],
            ['name' => $customerNames[3], 'value' => '15,7%', 'width' => 85],
            ['name' => $customerNames[1], 'value' => '13,2%', 'width' => 72],
            ['name' => $customerNames[2], 'value' => '11,6%', 'width' => 63],
        ],
    ];
@endphp

@if ($section)
<section class="growth-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    <div class="landing-shell growth-section__shell">
        <div class="growth-section__copy">
            @if ($section->title)
                <h2>{{ $section->title }}</h2>
            @endif

            <div class="growth-section__text">
                <p>
                    {{ $extra['lead_prefix'] ?? '' }}
                    <strong>{{ $extra['lead_highlight'] ?? '' }}</strong>{{ $extra['lead_suffix'] ?? '' }}
                </p>
                @if (! empty($extra['paragraph_two']))
                    <p>{{ $extra['paragraph_two'] }}</p>
                @endif
                @if (! empty($extra['paragraph_three']))
                    <p>{{ $extra['paragraph_three'] }}</p>
                @endif
            </div>
        </div>

        <div class="growth-dashboard" data-growth-dashboard aria-label="Примеры аналитических отчётов ЛогистРу">
            <article class="growth-card growth-card--margins">
                <header class="growth-card__header">
                    <div>
                        <h3>Сегменты маржинальности заявок</h3>
                        <p>Распределение по диапазонам маржи</p>
                    </div>
                    <div class="growth-switch" role="group" aria-label="Единица измерения">
                        <button class="is-active" type="button" data-growth-unit="percent" aria-pressed="true">%</button>
                        <button type="button" data-growth-unit="count" aria-pressed="false">шт.</button>
                    </div>
                </header>

                <div class="growth-donut" role="img" aria-label="Распределение маржинальности заявок">
                    <div class="growth-donut__center">
                        <strong data-growth-total>100%</strong>
                        <span data-growth-total-label>Доля</span>
                    </div>
                </div>

                <div class="growth-legend">
                    @foreach ($marginSegments as $segment)
                        <div class="growth-legend__row">
                            <span class="growth-dot {{ $segment['class'] }}" aria-hidden="true"></span>
                            <span>{{ $segment['label'] }}</span>
                            <strong data-growth-percent="{{ $segment['value'] }}" data-growth-count="{{ $segment['count'] }}">{{ $segment['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="growth-card growth-card--customers">
                <header class="growth-card__header growth-card__header--customers">
                    <h3>Топ заказчиков</h3>
                    <div class="growth-tabs" role="tablist" aria-label="Показатель рейтинга">
                        <button class="is-active" type="button" role="tab" data-growth-view="count" aria-selected="true">По количеству заявок</button>
                        <button type="button" role="tab" data-growth-view="revenue" aria-selected="false" tabindex="-1">По выручке</button>
                        <button type="button" role="tab" data-growth-view="margin" aria-selected="false" tabindex="-1">По маржинальности</button>
                    </div>
                </header>

                <div class="growth-customer-list" data-growth-customer-list aria-live="polite">
                    @foreach ($customers as $customer)
                        <div class="growth-customer">
                            <span class="growth-customer__name">{{ $customer['name'] }}</span>
                            <span class="growth-customer__track" aria-hidden="true">
                                <span style="width: {{ $customer['width'] }}%"></span>
                            </span>
                            <strong>{{ $customer['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
                <script type="application/json" data-growth-customer-data>@json($customerViews)</script>
            </article>
        </div>
    </div>
</section>
@endif
