@php
    $section = $landing->section('pricing_wide');
    $plan = $landing->blocks('pricing_wide', 'plan')->first();
    $planExtra = is_array($plan?->extra) ? $plan->extra : [];
    $features = $plan?->children->where('block_type', 'feature')->values() ?? collect();
    $paidOptions = $plan?->children->where('block_type', 'paid_option')->values() ?? collect();
    $usersMin = max(1, (int) ($planExtra['users_min'] ?? 1));
    $usersMax = min(500, max($usersMin, (int) ($planExtra['users_max'] ?? 20)));
    $usersDefault = min($usersMax, max($usersMin, (int) ($planExtra['users_default'] ?? $usersMin)));
    $basePrice = max(0, (int) ($plan?->price ?? 0));
    $initialPrice = $basePrice * $usersDefault;
    $currencySuffix = trim((string) ($planExtra['currency_suffix'] ?? ''));
    $yearCurrencySuffix = trim((string) ($planExtra['year_currency_suffix'] ?? ''));
    $workplaceForms = [
        'one' => trim((string) ($planExtra['workplace_one'] ?? '')),
        'few' => trim((string) ($planExtra['workplace_few'] ?? '')),
        'many' => trim((string) ($planExtra['workplace_many'] ?? '')),
    ];
    $workplaceNoun = static function (int $count) use ($workplaceForms): string {
        $lastTwoDigits = $count % 100;
        $lastDigit = $count % 10;

        return $lastTwoDigits >= 11 && $lastTwoDigits <= 14
            ? $workplaceForms['many']
            : match ($lastDigit) {
                1 => $workplaceForms['one'],
                2, 3, 4 => $workplaceForms['few'],
                default => $workplaceForms['many'],
            };
    };
    $usersNoteTemplate = trim((string) ($plan?->description ?? ''));
    $initialUsersNote = strtr($usersNoteTemplate, [
        '{users}' => number_format($usersDefault, 0, ',', ' '),
        '{workplaces}' => $workplaceNoun($usersDefault),
    ]);
@endphp

@if ($section && $plan)
<section class="pricing-wide-section" @if($section->anchorId()) id="{{ $section->anchorId() }}" @endif>
    <div class="landing-shell">
        <header class="section-head section-head--wide pricing-wide-head">
            @if ($section->title)
                <h2>{{ $section->title }}</h2>
            @endif
            @if ($section->subtitle)
                <p>{{ $section->subtitle }}</p>
            @endif
        </header>

        <article
            class="pricing-card pricing-card--wide"
            data-wide-pricing
            data-base-price="{{ $basePrice }}"
            data-wide-pricing-users-note-template="{{ $usersNoteTemplate }}"
            data-wide-pricing-workplace-forms="{{ json_encode($workplaceForms, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) }}"
            data-wide-pricing-period="month"
            data-month-currency-suffix="{{ $currencySuffix }}"
            data-year-currency-suffix="{{ $yearCurrencySuffix }}"
        >
            <div class="pricing-card--wide__details">
                <div class="pricing-card--wide__intro">
                    @if ($plan->title)
                        <h3>{{ $plan->title }}</h3>
                    @endif
                    @if ($plan->subtitle)
                        <p class="pricing-card__desc">{{ $plan->subtitle }}</p>
                    @endif
                </div>
                <div class="pricing-card--wide__summary">
                    <div class="pricing-card__price" aria-live="polite">
                        <span data-wide-pricing-total>{{ number_format($initialPrice, 0, ',', ' ') }}</span>
                        @if ($currencySuffix !== '')
                            <small data-wide-pricing-suffix>{{ $currencySuffix }}</small>
                        @endif
                    </div>
                    <p
                        class="pricing-card__price-note"
                        data-wide-pricing-users-note
                    >
                        {{ $initialUsersNote }}
                    </p>
                </div>
            </div>

            <div class="pricing-card--wide__features">
                <ul>
                    @foreach ($features as $feature)
                        <li>
                            <svg viewBox="0 0 16 16" width="16" height="16" fill="none" aria-hidden="true" class="pricing-card__check">
                                <use href="#icon-doc-check-circle" xlink:href="#icon-doc-check-circle" />
                            </svg>
                            {{ $feature->title }}
                        </li>
                    @endforeach
                </ul>

                <div class="pricing-card--wide__controls">
                    <div class="pricing-card--wide__users">
                        <span>{{ $planExtra['users_label'] ?? '' }}</span>
                        <span class="pricing-card--wide__stepper">
                            <button type="button" data-wide-pricing-decrease aria-label="{{ $planExtra['users_decrease_label'] ?? '' }}">−</button>
                            <input
                                type="number"
                                value="{{ $usersDefault }}"
                                min="{{ $usersMin }}"
                                max="{{ $usersMax }}"
                                readonly
                                aria-label="{{ $planExtra['users_label'] ?? '' }}"
                                data-wide-pricing-users
                            >
                            <button type="button" data-wide-pricing-increase aria-label="{{ $planExtra['users_increase_label'] ?? '' }}">+</button>
                        </span>
                    </div>

                    <div class="pricing-card--wide__period">
                        <span>{{ $planExtra['period_label'] ?? '' }}</span>
                        <span class="pricing-card--wide__period-switch" role="group" aria-label="{{ $planExtra['period_label'] ?? '' }}">
                            <button type="button" data-wide-pricing-period-button data-period="month" aria-pressed="true">{{ $planExtra['month_label'] ?? '' }}</button>
                            <button type="button" data-wide-pricing-period-button data-period="year" aria-pressed="false">{{ $planExtra['year_label'] ?? '' }}</button>
                        </span>
                    </div>
                </div>
            </div>

            <div class="pricing-card--wide__additional-heading">
                @if ($paidOptions->isNotEmpty())
                    <h4>{{ $planExtra['additional_title'] ?? '' }}</h4>
                @endif
            </div>

            <div class="pricing-card--wide__configurator">
                @if ($paidOptions->isNotEmpty())
                    <div class="pricing-card--wide__options">
                        @foreach ($paidOptions as $option)
                            @php($optionPrice = max(0, (int) $option->price))
                            <label class="pricing-card--wide__option">
                                <input type="checkbox" value="{{ $optionPrice }}" data-option-id="{{ $option->id }}" data-wide-pricing-option>
                                <span class="pricing-card--wide__checkbox" aria-hidden="true"></span>
                                <span class="pricing-card--wide__option-title">{{ $option->title }}</span>
                                <strong>
                                    +<span data-wide-pricing-option-price data-monthly-price="{{ $optionPrice }}">{{ number_format($optionPrice, 0, ',', ' ') }}</span>
                                    <span data-wide-pricing-option-suffix>{{ $currencySuffix }}</span>
                                </strong>
                            </label>
                        @endforeach
                    </div>
                @endif

                @if ($plan->button_text)
                    <div class="pricing-card--wide__action">
                        <button type="button" data-commercial-offer-open @class(['btn', 'btn--primary' => $plan->button_style === 'primary', 'btn--ghost' => $plan->button_style !== 'primary'])>
                            {{ $plan->button_text }}
                        </button>
                    </div>
                @endif
            </div>
        </article>
    </div>
</section>
@endif
