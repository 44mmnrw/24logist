@php
    $section = $landing->section('growth');
    $extra = $section?->extra ?? [];
    $descriptionParagraphs = preg_split('/\R{2,}/u', trim((string) ($section?->description ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $marginSegments = collect($extra['margin_segments'] ?? [])
        ->values()
        ->map(fn (array $segment, int $index): array => [
            'label' => (string) ($segment['label'] ?? ''),
            'value' => (string) ($segment['percent_value'] ?? ''),
            'count' => (string) ($segment['count_value'] ?? ''),
            'percent' => max(0, (float) str_replace(',', '.', rtrim((string) ($segment['percent_value'] ?? '0'), "% \t\n\r\0\x0B"))),
            'color' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($segment['color'] ?? '')) ? (string) $segment['color'] : '#94a3b8',
        ]);
    $gradientPosition = 0.0;
    $gradientStops = $marginSegments->map(function (array $segment) use (&$gradientPosition): string {
        $start = $gradientPosition;
        $gradientPosition = min(100, $gradientPosition + $segment['percent']);

        return sprintf('%s %.2f%% %.2f%%', $segment['color'], $start, $gradientPosition);
    })->implode(', ');
    $customerMetrics = collect($extra['customer_metrics'] ?? [])->values();
    $customerViews = collect(['count', 'revenue', 'margin'])
        ->mapWithKeys(fn (string $view): array => [
            $view => $customerMetrics
                ->map(fn (array $customer): array => [
                    'name' => (string) ($customer['name'] ?? ''),
                    'value' => (string) ($customer[$view.'_value'] ?? ''),
                    'width' => (int) ($customer[$view.'_width'] ?? 0),
                ])
                ->sortByDesc('width')
                ->values()
                ->all(),
        ])
        ->all();
    $customers = $customerViews['count'];
@endphp

@if ($section)
<section class="growth-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    <div class="landing-shell growth-section__shell">
        <div class="growth-section__copy">
            @if ($section->title)
                <h2>{{ $section->title }}</h2>
            @endif

            <div class="growth-section__text">
                @foreach ($descriptionParagraphs as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
        </div>

        <div class="growth-dashboard" data-growth-dashboard aria-label="{{ $extra['dashboard_aria_label'] ?? '' }}">
            <article class="growth-card growth-card--margins">
                <header class="growth-card__header">
                    <div>
                        <h3>{{ $extra['chart_title'] ?? '' }}</h3>
                        <p>{{ $extra['chart_subtitle'] ?? '' }}</p>
                    </div>
                    <div class="growth-switch" role="group" aria-label="{{ $extra['unit_aria_label'] ?? '' }}">
                        <button class="is-active" type="button" data-growth-unit="percent" aria-pressed="true">{{ $extra['unit_percent_label'] ?? '' }}</button>
                        <button type="button" data-growth-unit="count" aria-pressed="false">{{ $extra['unit_count_label'] ?? '' }}</button>
                    </div>
                </header>

                <div class="growth-donut" role="img" aria-label="{{ $extra['chart_aria_label'] ?? '' }}" style="background: conic-gradient({{ $gradientStops }})">
                    <div class="growth-donut__center">
                        <strong data-growth-total data-percent-value="{{ $extra['total_percent_value'] ?? '' }}" data-count-value="{{ $extra['total_count_value'] ?? '' }}">{{ $extra['total_percent_value'] ?? '' }}</strong>
                        <span data-growth-total-label data-percent-label="{{ $extra['total_percent_label'] ?? '' }}" data-count-label="{{ $extra['total_count_label'] ?? '' }}">{{ $extra['total_percent_label'] ?? '' }}</span>
                    </div>
                </div>

                <div class="growth-legend">
                    @foreach ($marginSegments as $segment)
                        <div class="growth-legend__row">
                            <span class="growth-dot" style="background: {{ $segment['color'] }}" aria-hidden="true"></span>
                            <span>{{ $segment['label'] }}</span>
                            <strong data-growth-percent="{{ $segment['value'] }}" data-growth-count="{{ $segment['count'] }}">{{ $segment['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="growth-card growth-card--customers">
                <header class="growth-card__header growth-card__header--customers">
                    <h3>{{ $extra['customers_title'] ?? '' }}</h3>
                    <div class="growth-tabs" role="tablist" aria-label="{{ $extra['tabs_aria_label'] ?? '' }}">
                        <button class="is-active" type="button" role="tab" data-growth-view="count" aria-selected="true">{{ $extra['tab_count_label'] ?? '' }}</button>
                        <button type="button" role="tab" data-growth-view="revenue" aria-selected="false" tabindex="-1">{{ $extra['tab_revenue_label'] ?? '' }}</button>
                        <button type="button" role="tab" data-growth-view="margin" aria-selected="false" tabindex="-1">{{ $extra['tab_margin_label'] ?? '' }}</button>
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
